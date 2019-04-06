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

class Actions
{
    protected $namespace;

    protected $namespaceLen;

    protected $instances = [];

    protected $wordSeparator;

    protected $directory;

    protected $method;

    protected $baseUrl;

    protected $baseUrlLen;

    protected $ignoreParams;

    protected $suffix;

    protected $suffixLen;

    public function __construct(
        string $namespace,
        string $directory,
        string $suffix,
        string $method,
        int $ignoreParams,
        string $baseUrl,
        string $wordSeparator
    ) {
        $this->namespace = trim($namespace, '\\') . '\\';
        $this->namespaceLen = strlen($this->namespace);
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
        $this->suffix = $suffix;
        $this->suffixLen = strlen($suffix);
        $this->method = $method;
        $this->ignoreParams = $ignoreParams;
        $this->baseUrl = trim($baseUrl, '/');
        $this->baseUrlLen = strlen($this->baseUrl);
        $this->wordSeparator = $wordSeparator;
    }

    public function getNamespace() : string
    {
        return $this->namespace;
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function getDirectory() : string
    {
        return $this->directory;
    }

    public function segmentToNamespace(string $segment) : string
    {
        $segment = trim($segment);

        if ($segment === '') {
            throw new InvalidNamespace("Cannot convert empty segment to namespace part");
        }

        return str_replace(
            $this->wordSeparator,
            '',
            ucwords($segment, $this->wordSeparator)
        );
    }

    public function namespaceToSegment(string $part) : string
    {
        $part = trim($part);
        return strtolower(preg_replace(
            '/([a-z])([A-Z])/',
            "\$1{$this->wordSeparator}\$2",
            $part
        ));
    }

    public function getAction(string $class) : Action
    {
        if (! isset($this->instances[$class])) {
            $this->instances[$class] = $this->newAction($class);
        }

        return $this->instances[$class];
    }

    protected function newAction(string $class) : Action
    {
        $ns = substr($class, 0, $this->namespaceLen);
        if ($ns !== $this->namespace) {
            throw new InvalidNamespace("Expected namespace {$this->namespace}, actually $class");
        }

        if (! class_exists($class)) {
            throw new NotFound("Expected class $class, actually not found");
        }

        return new Action(
            $this->namespace,
            $class,
            $this->suffix,
            $this->method,
            $this->ignoreParams,
            $this->baseUrl
        );
    }

    public function generate(string $class) : array
    {
        return $this->getAction($class)->generate($this);
    }

    public function dump(string $class) : array
    {
        return $this->getAction($class)->dump($this);
    }

    public function isSubNamespace(string $subns) : bool
    {
        if (substr($subns, -2) == '..') {
            throw new InvalidNamespace("Directory dots not allowed in segments");
        }

        $dir = $this->directory . str_replace('\\', DIRECTORY_SEPARATOR, $subns);
        return is_dir($dir);
    }

    public function getSegments(string $path) : array
    {
        $path = trim($path, '/');
        $base = substr($path, 0, $this->baseUrlLen);
        if ($base !== $this->baseUrl) {
            throw new NotFound("Expected base URL /$this->baseUrl, actually /$base");
        }

        $segments = [];

        $path = trim(substr($path, $this->baseUrlLen), '/');
        if (! empty($path)) {
            $segments = explode('/', $path);
        }

        return $segments;
    }

    public function actionExists(
        string $verb,
        string $subNamespace,
        string $append = ''
    ) : ?string
    {
        $class = rtrim($this->namespace, '\\')
            . $subNamespace
            . '\\';

        if ($append !== '') {
            $class .= $append . '\\';
        }

        $class .= $verb . str_replace('\\', '', $subNamespace . $append) . $this->suffix;
        if (class_exists($class)) {
            return $class;
        }

        return null;
    }

    public function fileToClass(string $file) : ?string
    {
        $file = str_replace($this->directory . DIRECTORY_SEPARATOR, '', substr($file, 0, -4));
        $parts = explode(DIRECTORY_SEPARATOR, $file);

        $last = array_pop($parts);
        $core = implode('', $parts);
        $verb = substr($last, 0, strlen($last) - strlen($core) - $this->suffixLen);
        if ($verb === '') {
            return null;
        }

        $subNamespace = '';
        if (! empty($parts)) {
            $subNamespace = '\\' . implode('\\', $parts);
        }

        return $this->actionExists($verb, $subNamespace);
    }
}
