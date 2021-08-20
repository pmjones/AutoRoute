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

use ReflectionClass;
use ReflectionParameter;
use ReflectionNamedType;

class Reflector
{
    protected array $actionParameters = [];

    protected array $classes = [];

    protected array $constructorParameters = [];

    public function __construct(protected Config $config)
    {
    }

    public function getConstructorParameters(string $class) : array
    {
        if (! isset($this->constructorParameters[$class])) {
            $this->setConstructorParameters($class);
        }

        return $this->constructorParameters[$class];
    }

    protected function setConstructorParameters(string $class) : void
    {
        $this->constructorParameters[$class] = [];
        $rclass = $this->getClass($class);
        $rctor = $rclass->getConstructor();

        if ($rctor !== null) {
            $this->constructorParameters[$class] = $rctor->getParameters();
        }
    }

    public function getActionParameters(string $class) : array
    {
        if (! isset($this->actionParameters[$class])) {
            $this->setActionParameters($class);
        }

        return $this->actionParameters[$class];
    }

    protected function setActionParameters(string $class) : void
    {
        $this->actionParameters[$class] = [];
        $rclass = $this->getClass($class);
        $rmethod = $rclass->getMethod($this->config->method);

        if ($rmethod !== null) {
            $this->actionParameters[$class] = $rmethod->getParameters();
        }
    }

    protected function getClass(string $class) : ReflectionClass
    {
        if (! class_exists($class)) {
            throw new Exception\NotFound("Class not found: {$class}");
        }

        if (! isset($this->classes[$class])) {
            $this->classes[$class] = new ReflectionClass($class);
        }

        return $this->classes[$class];
    }

    public function getParameterType(ReflectionParameter $parameter) : string
    {
        $type = $parameter->getType();
        return $type === null ? 'mixed' : $type->getName();
    }
}
