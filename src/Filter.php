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

use ReflectionParameter;

class Filter
{
    public function __construct(protected Reflector $reflector)
    {
    }

    public function parameter(ReflectionParameter $rp, array &$values) : mixed
    {
        $type = $this->reflector->getParameterType($rp);

        if (class_exists($type)) {
            return $this->toObject($rp, $type, $values);
        }

        $value = array_shift($values);

        if ($this->isBlank($value)) {
            throw $this->invalidArgument($rp, 'non-blank', $value);
        }

        $method = 'to' . ucfirst($type);
        return $this->$method($rp, $value);
    }

    protected function isBlank(mixed $value) : bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return empty($value);
        }

        return false;
    }

    protected function toArray(ReflectionParameter $rp, mixed $value) : array
    {
        if (is_array($value)) {
            return $value;
        }

        return str_getcsv((string) $value);
    }

    protected function toBool(ReflectionParameter $rp, mixed $value) : bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (in_array(strtolower($value), ['1', 't', 'true', 'y', 'yes'])) {
            return true;
        }

        if (in_array(strtolower($value), ['0', 'f', 'false', 'n', 'no'])) {
            return false;
        }

        throw $this->invalidArgument($rp, 'boolean-equivalent', $value);
    }

    protected function toInt(ReflectionParameter $rp, mixed $value) : int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value) && (int) $value == $value) {
            return (int) $value;
        }

        throw $this->invalidArgument($rp, 'numeric integer', $value);
    }

    protected function toFloat(ReflectionParameter $rp, mixed $value) : float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        throw $this->invalidArgument($rp, 'numeric float', $value);
    }

    protected function toString(ReflectionParameter $rp, mixed $value) : string
    {
        return (string) $value;
    }

    protected function toMixed(ReflectionParameter $rp, mixed $value) : string
    {
        return (string) $value;
    }

    protected function toObject(
        ReflectionParameter $rp,
        string $type,
        array &$values
    ) : object
    {
        $args = [];
        $ctorParams = $this->reflector->getConstructorParameters($type);

        while (! empty($values) && ! empty($ctorParams)) {
            $ctorParam = array_shift($ctorParams);
            $paramType = $ctorParam->getType()->getName();
            $method = 'to' . ucfirst($paramType);
            $args[] = $this->$method($ctorParam, array_shift($values));
        }

        return new $type(...$args);
    }

    protected function invalidArgument(
        ReflectionParameter $rp,
        string $type,
        mixed $value
    ) : Exception\InvalidArgument
    {
        $pos = $rp->getPosition();
        $name = $rp->getName();
        $class = $rp->getDeclaringClass()->getName();
        $method = $rp->getDeclaringFunction()->getName();
        $value = var_export($value, true);
        return new Exception\InvalidArgument(
            "Expected {$type} argument "
            . "for {$class}::{$method}() "
            . "parameter {$pos} (\$$name), "
            . "actually $value"
        );
    }
}
