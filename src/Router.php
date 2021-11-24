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

    protected array $messages = [];

    public function __construct(
        protected Config $config,
        protected Actions $actions,
        protected Filter $filter,
        protected LoggerInterface $logger,
    ) {
    }

    public function getLogger() : LoggerInterface
    {
        return $this->logger;
    }

    public function route(string $verb, string $path) : Route
    {
        $this->verb = ucfirst(strtolower($verb));
        $this->subNamespace = '';
        $this->class = '';
        $this->action = new Action('', [], []);
        $this->arguments = [];
        $this->messages = [];
        $this->log("{$verb} {$path}");

        try {
            $this->capture($path);
            return new Route(
                $this->action->getClass(),
                $this->config->method,
                $this->arguments,
                messages: $this->messages,
            );
        } catch (Throwable $e) {
            return new Route(
                $this->class,
                $this->config->method,
                $this->arguments,
                get_class($e),
                $e,
                $this->headers,
                $this->messages,
            );
        }
    }

    protected function capture(string $path) : void
    {
        $this->segments = $this->getSegments($path);

        do {
            $this->captureNextSegment();
        } while (! empty($this->segments));

        $this->log('segments empty, capture complete');

        $requiredCount = count($this->action->getRequiredParameters());
        $argumentCount = count($this->arguments);

        if ($argumentCount >= $requiredCount) {
            return;
        }

        $message = "{$this->class} needs {$requiredCount} argument(s), "
            . "{$argumentCount} found";

        $this->log($message);
        throw new Exception\NotFound($message);
    }

    protected function captureNextSegment() : void
    {
        $subNamespace = '';

        // capture next segment as a subnamespace
        if (! empty($this->segments)) {
            $segment = $this->segmentToNamespace(reset($this->segments));
            $this->log("candidate namespace segment: {$segment}");
            $subNamespace = $this->subNamespace . '\\' . $segment;
        }

        $this->log("find subnamespace: {$subNamespace}");

        // does the subnamespace exist?
        if (! $this->actions->hasSubNamespace($subNamespace)) {
            $ns = rtrim($this->config->namespace, '\\') . $subNamespace;
            $this->log('subnamespace not found');

            // are we are the very top of the url?
            if ($this->subNamespace === '') {
                // yes, try to capture arguments for it
                $this->captureCatchallClass();
                return;
            }

            // no, so no need to keep matching
            throw new Exception\NotFound("Not a known namespace: {$ns}");
        }

        $this->log('subnamespace found');
        $this->subNamespace = $subNamespace;
        array_shift($this->segments);
        $this->captureMainClass();
    }

    protected function captureCatchallClass() : void
    {
        $expect = $this->actions->getClass($this->verb, $this->subNamespace);
        $this->log("find catchall class: {$expect}");
        $class = $this->actions->hasAction($this->verb, $this->subNamespace);

        if ($class === null) {
            $this->log('catchall class not found');
            throw new Exception\NotFound("Catchall class for {$this->verb} not found");
        }

        $this->log('catchall class found');
        $this->class = $class;
        $this->action = $this->actions->getAction($this->class);

        $this->log('capture all segments as argments');

        $this->captureRequiredArguments();
        $this->captureOptionalArguments();

        if (empty($this->segments)) {
            return;
        }

        $this->log('leftover segments');
        $class = $this->action->getClass();
        throw new Exception\NotFound("Too many router segments for {$class}");
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

        $this->log('class found');
        $this->class = $class;
        $this->action = $this->actions->getAction($this->class);

        if (count($this->segments) === 1) {
            $this->captureTailClass();
        }

        $this->captureRequiredArguments();
        $this->captureOptionalArguments();
    }

    protected function classNotFound() : void
    {
        $this->log('class not found');

        if (! empty($this->segments)) {
            return;
        }

        // no class, and no more segments
        $this->log('segments empty too soon');
        $ns = rtrim($this->config->namespace, '\\') . $this->subNamespace;
        $allowed = $this->actions->getAllowed($this->subNamespace);

        if (empty($allowed)) {
            throw new Exception\NotFound("No actions found in namespace {$ns}");
        }

        $verb = strtoupper($this->verb);
        $this->headers = ['allowed' => implode(',', $allowed)];
        throw new Exception\MethodNotAllowed(
            "{$verb} action not found in namespace {$ns}"
        );
    }

    protected function captureTailClass() : void
    {
        $segment = $this->segmentToNamespace($this->segments[0]);
        $this->log("candidate static tail namepace segment: {$segment}");
        $tailClass = $this->actions->hasAction(
            $this->verb,
            $this->subNamespace,
            $segment
        );

        if ($tailClass === null) {
            $this->log('static tail subnamespace not found');
            return;
        }

        $this->log("static tail class found: {$tailClass}");
        array_shift($this->segments);
        $this->subNamespace .= '\\' . $segment;
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

    protected function captureOptionalArguments() : void
    {
        if (empty($this->segments)) {
            return;
        }

        $segment = $this->segmentToNamespace($this->segments[0]);
        $temp = $this->subNamespace . '\\' . $segment;

        if ($this->actions->hasSubNamespace($temp)) {
            $this->log('next segment is a namespace');
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

        $this->log('leftover segments');
        $class = $this->action->getClass();
        throw new Exception\NotFound("Too many router segments for {$class}");
    }

    protected function captureArguments(array $parameters) : void
    {
        foreach ($parameters as $i => $parameter) {
            $this->captureArgument($parameter, $i);
        }
    }

    protected function captureArgument(
        ReflectionParameter $parameter,
        int $i
    ) : void
    {
        if (empty($this->segments)) {
            return;
        }

        if ($parameter->isVariadic()) {
            $this->captureVariadic($parameter, $i);
            return;
        }

        $this->arguments[] = $this->filter->parameter(
            $parameter,
            $this->segments
        );
        $name = $parameter->getName();
        $this->log("captured argument {$i} (\${$name})");
    }

    protected function captureVariadic(
        ReflectionParameter $parameter,
        int $i
    ) : void
    {
        $name = $parameter->getName();

        while (! empty($this->segments)) {
            $this->arguments[] = $this->filter->parameter(
                $parameter,
                $this->segments
            );
            $this->log("captured variadic argument {$i} (\${$name})");
        }
    }

    protected function segmentToNamespace(string $segment) : string
    {
        $segment = trim($segment);

        if ($segment === '') {
            throw new Exception\NotFound(
                'Cannot convert empty segment to namespace part'
            );
        }

        return str_replace(
            $this->config->wordSeparator,
            '',
            ucwords($segment, $this->config->wordSeparator)
        );
    }

    protected function getSegments(string $path) : array
    {
        $path = trim($path, '/');
        $base = substr($path, 0, $this->config->baseUrlLen);

        if ($base !== $this->config->baseUrl) {
            throw new Exception\NotFound(
                "Expected base URL /{$this->config->baseUrl}, actually /{$base}"
            );
        }

        $segments = [];

        $path = trim(substr($path, $this->config->baseUrlLen), '/');

        if (! empty($path)) {
            $segments = explode('/', $path);
        }

        return $segments;
    }

    protected function log(string $message) : void
    {
        $this->messages[] = $message;
        $this->logger->debug($message);
    }
}
