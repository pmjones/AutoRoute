<?php
declare(strict_types=1);

namespace AutoRoute;

use ReflectionClass;
use ReflectionParameter;

class Action
{
    protected $namespace;

    protected $namespaceLen;

    protected $class;

    protected $suffix;

    protected $suffixLen;

    protected $method;

    protected $parameters = [];

    protected $required = 0;

    protected $optional = 0;

    protected $variadic = false;

    protected $baseUrl;

    protected $verb;

    protected $path;

    protected $argc;

    protected $generated = false;

    public function __construct(
        string $namespace,
        string $class,
        string $suffix,
        string $method,
        int $ignoreParams,
        string $baseUrl
    ) {
        $this->namespace = trim($namespace, '\\') . '\\';
        $this->namespaceLen = strlen($this->namespace);
        $this->class = $class;
        $this->suffix = $suffix;
        $this->suffixLen = strlen($suffix);
        $this->method = $method;
        $this->baseUrl = '/' . trim($baseUrl, '/');

        $rc = new ReflectionClass($this->class);
        $rm = $rc->getMethod($this->method);

        $this->parameters = $rm->getParameters();
        for ($i = 0; $i < $ignoreParams; $i ++) {
            array_shift($this->parameters);
        }

        $this->required = $rm->getNumberOfRequiredParameters() - $ignoreParams;

        $this->variadic = empty($this->parameters)
            ? false
            : end($this->parameters)->isVariadic();

        $this->optional = $rm->getNumberOfParameters()
            - $this->required
            - $ignoreParams
            - (int) $this->variadic;
    }

    public function getClass() : string
    {
        return $this->class;
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function getClassMethod() : string
    {
        return $this->class . '::' . $this->method . '()';
    }

    public function getRequired() : int
    {
        return $this->required;
    }

    public function getOptional() : int
    {
        return $this->optional;
    }

    public function hasOptionals() : bool
    {
        return $this->optional > 0 || $this->variadic;
    }

    public function hasVariadic() : bool
    {
        return $this->variadic;
    }

    public function getParameters() : array
    {
        return $this->parameters;
    }

    public function generate(Actions $actions) : array
    {
        if ($this->generated) {
            return [$this->verb, $this->path, $this->argc];
        }

        $parts = explode('\\', substr($this->class, $this->namespaceLen));
        $last = array_pop($parts);
        $impl = implode('', $parts);

        $this->verb = substr($last, 0, strlen($last) - strlen($impl) - $this->suffixLen);

        $this->path = $this->baseUrl;
        if ($this->baseUrl == '/' && ! empty($parts)) {
            $this->path = '';
        }

        $this->argc = 0;

        $subNamespace = '';
        $currClass = '';

        while (! empty($parts)) {
            $this->generateSegments($actions, $subNamespace, $currClass, $parts);
        }

        $this->generated = true;
        return [$this->verb, $this->path, $this->argc];
    }

    protected function generateSegments(
        Actions $actions,
        string &$subNamespace,
        string &$currClass,
        array &$parts
    ) : void {
        $prevClass = $currClass;

        $part = array_shift($parts);
        $this->path .= '/' . $actions->namespaceToSegment($part);

        $subNamespace .= '\\' . $part;
        $class = $actions->actionExists($this->verb, $subNamespace);
        if ($class === null) {
            return;
        }

        $staticTailSegment = $this->generateStaticTailSegment(
            $actions,
            $subNamespace,
            $prevClass,
            $parts
        );

        if ($staticTailSegment) {
            return;
        }

        $currClass = $class;
        $action = $actions->getAction($currClass);
        $this->generateRequiredSegments($action);
    }

    protected function generateStaticTailSegment(
        Actions $actions,
        string &$subNamespace,
        string &$prevClass,
        array &$parts
    ) {
        if (count($parts) !== 1) {
            return false;
        }

        $prevRequired = 0;
        if ($prevClass !== '') {
            $prevRequired = $actions->getAction($prevClass)->required;
        }

        $nextClass = $actions->actionExists($this->verb, $subNamespace, $parts[0]);
        $nextRequired = $actions->getAction($nextClass)->required;
        if ($prevRequired !== $nextRequired) {
            return false;
        }

        $part = array_shift($parts);
        $this->path .= '/' . $actions->namespaceToSegment($part);
        return true;
    }

    protected function generateRequiredSegments(Action $action) : void
    {
        $count = $action->getRequired() - $this->argc;
        while ($count > 0) {
            $this->path .= '/{' . $this->argc . '}';
            $this->argc ++;
            $count --;
        }
    }

    public function dump(Actions $actions) : array
    {
        $this->generate($actions);

        $namedPath = $this->path;
        $pairs = [];

        foreach ($this->parameters as $pos => $rp) {
            $this->dumpNamedPathPairs($pos, $rp, $namedPath, $pairs);
        }

        return [$this->verb, strtr($namedPath, $pairs), $this->argc];
    }

    protected function dumpNamedPathPairs(
        int $pos,
        $rp,
        string &$namedPath,
        array &$pairs
    ) {
        if ($pos < $this->argc) {
            $key = '{' . $pos . '}';
            $pairs[$key] = $this->dumpNamedToken($rp);
        } else {
            $namedPath .= '[/' . $this->dumpNamedToken($rp) . ']';
        }
    }

    protected function dumpNamedToken(ReflectionParameter $rp) : string
    {
        $token = '{';
        $type = (string) $rp->getType();
        if ($type !== '') {
            $token .= "$type:";
        } else {
            $token .= "string:";
        }
        if ($rp->isVariadic()) {
            $token .= '...';
        }
        $token .= $rp->getName() . '}';
        return $token;
    }
}
