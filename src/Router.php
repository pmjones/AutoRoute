<?php
/**
 *
 * This file is part of AutoRoute for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 */
declare(strict_types=1);

namespace AutoRoute;

use DirectoryIterator;
use Psr\Log\LoggerInterface;
use ReflectionParameter;
use Throwable;

class Router
{
    protected Action $action;

    protected array $arguments = [];

    protected string $class = '';

    protected array $segments = [];

    protected string $subNamespace = '';

    protected string $verb = '';

    protected array $headers = [];

    public function __construct(
        protected Config $config,
        protected Actions $actions,
        protected Filter $filter,
        protected LoggerInterface $logger,
    ) {
    }

    public function route(string $verb, string $path) : Route
    {
        $this->log("{$verb} {$path}");
        $this->verb = ucfirst(strtolower($verb));
        $this->subNamespace = '';
        $this->class = '';
        $this->action = new NullAction();
        $this->arguments = [];

        try {
            $this->segments = $this->getSegments($path);
            $this->captureLoop();

            return new Route(
                $this->action->getClass(),
                $this->config->method,
                $this->arguments
            );
        } catch (Throwable $e) {
            return new Route(
                $this->class,
                $this->config->method,
                $this->arguments,
                get_class($e),
                $e,
                $this->headers
            );
        }
    }

    protected function captureLoop() : void
    {
        do {
            $this->capture();
        } while (! empty($this->segments));

        $this->log("segments empty");
    }

    protected function capture() : void
    {
        $this->captureSubNamespace();
        $this->captureMainClass();
        $this->captureTailClass();
        $this->captureRequiredArguments();

        if ($this->nextSegmentIsNamespace()) {
            return;
        }

        $this->captureOptionalArguments();
    }

    protected function captureSubNamespace() : void
    {
        // consume next segment as a subnamespace
        if (! empty($this->segments)) {
            $segment = $this->segmentToNamespace(array_shift($this->segments));
            $this->log("candidate namespace segment: {$segment}");
            $this->subNamespace .= '\\' . $segment;
        }

        $this->log("find subnamespace: {$this->subNamespace}");

        // does the subnamespace exist?
        if (! $this->isSubNamespace($this->subNamespace)) {
            // no, so no need to keep matching
            $ns = rtrim($this->config->namespace, '\\') . $this->subNamespace;
            $this->log("subnamespace not found");
            throw new Exception\NotFound("Not a known namespace: $ns");
        }

        $this->log("subnamespace found");
    }

    protected function classNotFound() : void
    {
        $this->log("class not found");

        if (! empty($this->segments)) {
            // recursively capture next segment
            $this->capture();
            return;
        }

        // no class, and no more segments
        $this->log("segments empty");
        $ns = rtrim($this->config->namespace, '\\') . $this->subNamespace;
        $allowed = $this->getAllowed();

        if ($allowed === '') {
            throw new Exception\NotFound("No actions found in namespace {$ns}");
        }

        $verb = strtoupper($this->verb);
        $this->headers = ['allowed' => $allowed];
        throw new Exception\MethodNotAllowed("$verb action not found in namespace $ns");
    }

    protected function captureMainClass() : void
    {
        $expect = $this->actions->getClass($this->verb, $this->subNamespace);
        $this->log("find class: {$expect}");
        $class = $this->actions->hasAction($this->verb, $this->subNamespace);

        if ($class === null) {
            $this->classNotFound();
            return;
        }

        $this->log("class found");
        $this->class = $class;
        $this->action = $this->actions->getAction($this->class);
    }

    protected function captureTailClass() : void
    {
        // there can be only one segment remaining
        if (count($this->segments) !== 1) {
            return;
        }

        $segment = $this->segmentToNamespace($this->segments[0]);
        $this->log("candidate static tail namepace segment: {$segment}");
        $tailClass = $this->actions->hasAction($this->verb, $this->subNamespace, $segment);

        if ($tailClass === null) {
            $this->log("static tail subnamespace not found");
            return;
        }

        array_shift($this->segments);
        $this->subNamespace .= '\\' . $segment;
        $this->log("static tail class found: {$tailClass}");
        $this->class = $tailClass;
        $this->action = $this->actions->getAction($this->class);
    }

    protected function captureRequiredArguments() : void
    {
        if (empty($this->segments)) {
            return;
        }

        $offset = count($this->arguments);
        $requiredParameters = $this->action->getRequiredParameters($offset);

        if (empty($requiredParameters)) {
            $this->log('no additional required arguments');
            return;
        }

        $this->log('capture additional required arguments');
        $this->captureArguments($requiredParameters);
    }

    protected function nextSegmentIsNamespace() : bool
    {
        if (empty($this->segments)) {
            return false;
        }

        $segment = $this->segmentToNamespace($this->segments[0]);
        $temp = $this->subNamespace . '\\' . $segment;

        if ($this->isSubNamespace($temp)) {
            return true;
        }

        return false;
    }

    protected function captureOptionalArguments() : void
    {
        if (empty($this->segments)) {
            return;
        }

        $optionalParameters = $this->action->getOptionalParameters();

        if (empty($optionalParameters)) {
            $this->log('no optional arguments');
            return;
        }

        $this->log('capture optional arguments');
        $this->captureArguments($optionalParameters);

        if (empty($this->segments)) {
            return;
        }

        $this->log("leftover segments");
        $class = $this->action->getClass();
        throw new Exception\NotFound("Too many router segments for {$class}");
    }

    protected function captureArguments(array $parameters) : void
    {
        if (empty($this->segments)) {
            return;
        }

        foreach ($parameters as $i => $parameter) {
            $this->captureArgument($parameter, $i);
        }
    }

    protected function captureArgument(ReflectionParameter $parameter, int $i) : void
    {
        if (empty($this->segments)) {
            return;
        }

        if ($parameter->isVariadic()) {
            $this->captureVariadic($parameter, $i);
            return;
        }

        $this->arguments[] = $this->filter->parameter($parameter, $this->segments);
        $name = $parameter->getName();
        $this->log("captured argument {$i} (\${$name})");
    }

    protected function captureVariadic(ReflectionParameter $parameter, int $i) : void
    {
        $name = $parameter->getName();

        while (! empty($this->segments)) {
            $this->arguments[] = $this->filter->parameter($parameter, $this->segments);
            $this->log("captured variadic argument {$i} (\${$name})");
        }
    }

    protected function segmentToNamespace(string $segment) : string
    {
        $segment = trim($segment);

        if ($segment === '') {
            throw new Exception\NotFound("Cannot convert empty segment to namespace part");
        }

        return str_replace(
            $this->config->wordSeparator,
            '',
            ucwords($segment, $this->config->wordSeparator)
        );
    }

    protected function isSubNamespace(string $subns) : bool
    {
        if (substr($subns, -2) == '..') {
            throw new Exception\NotFound("Directory dots not allowed in segments");
        }

        $dir = $this->config->directory . str_replace('\\', DIRECTORY_SEPARATOR, $subns);
        return is_dir($dir);
    }

    protected function getSegments(string $path) : array
    {
        $path = trim($path, '/');
        $base = substr($path, 0, $this->config->baseUrlLen);

        if ($base !== $this->config->baseUrl) {
            throw new Exception\NotFound("Expected base URL /{$this->config->baseUrl}, actually /{$base}");
        }

        $segments = [];

        $path = trim(substr($path, $this->config->baseUrlLen), '/');

        if (! empty($path)) {
            $segments = explode('/', $path);
        }

        return $segments;
    }

    public function getLogger() : LoggerInterface
    {
        return $this->logger;
    }

    protected function log(string $message) : void
    {
        $this->logger->debug($message);
    }

    protected function getAllowed() : string
    {
        $verbs = [];
        $class = $this->actions->getClass('', $this->subNamespace);
        $parts = explode('\\', $class);
        $main = end($parts). '.php';
        $mainLen = -1 * strlen($main);
        $dir = $this->config->directory . str_replace('\\', DIRECTORY_SEPARATOR, $this->subNamespace);
        $items = new DirectoryIterator($dir);

        foreach ($items as $item) {
            $file = $item->getFilename();

            if (substr($file, -4) !== '.php') {
                continue;
            }

            $verb = substr($file, 0, $mainLen);

            if ($verb !== '') {
                $verbs[] = strtoupper($verb);
            }
        }

        if (in_array('GET', $verbs) && ! in_array('HEAD', $verbs)) {
            $verbs[] = 'HEAD';
        }

        sort($verbs);
        return implode(',', $verbs);
    }
}
