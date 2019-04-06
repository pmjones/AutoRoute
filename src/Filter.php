<?php
declare(strict_types=1);

namespace AutoRoute;

use ReflectionParameter;

class Filter
{
    public function forAction(ReflectionParameter $rp, /* mixed */ $value) // : mixed
    {
        $isBlank = ($value === null);

        if (is_string($value)) {
            $isBlank = trim($value) === '';
        }

        if (is_array($value)) {
            $isBlank = empty($value);
        }

        if ($isBlank) {
            throw $this->invalidArgument($rp, 'non-blank', $value);
        }

        $type = $rp->getType();
        if ($type === null) {
            $type = 'string';
        } else {
            $type = $type->getName();
        }

        $method = 'to' . ucfirst($type);
        return $this->$method($rp, $value);
    }

    public function forSegment(ReflectionParameter $rp, /* mixed */ $value) : string
    {
        // do no capture the filtered value, just validate it
        $this->forAction($rp, $value);

        // only arrays cannot be cast to string
        if (is_array($value)) {
            return implode(',', $value);
        }

        // return what was passed, cast as a string for the URL path segment
        return (string) $value;
    }

    protected function toArray(ReflectionParameter $rp, /* mixed */ $value) : array
    {
        if (is_array($value)) {
            return $value;
        }

        return str_getcsv((string) $value);
    }

    protected function toBool(ReflectionParameter $rp, /* mixed */ $value) : bool
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

    protected function toInt(ReflectionParameter $rp, /* mixed */ $value) : int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value) && (int) $value == $value) {
            return (int) $value;
        }

        throw $this->invalidArgument($rp, 'numeric integer', $value);
    }

    protected function toFloat(ReflectionParameter $rp, /* mixed */ $value) : float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        throw $this->invalidArgument($rp, 'numeric float', $value);
    }

    protected function toString(ReflectionParameter $rp, /* mixed */ $value) : string
    {
        return (string) $value;
    }

    protected function invalidArgument(ReflectionParameter $rp, string $type, /* mixed */ $value) : InvalidArgument
    {
        $pos = $rp->getPosition();
        $name = $rp->getName();
        $class = $rp->getDeclaringClass()->getName();
        $method = $rp->getDeclaringFunction()->getName();
        $value = var_export($value, true);
        return new InvalidArgument("Expected {$type} argument for {$class}::{$method}() parameter {$pos} (\$$name), actually $value");
    }
}
