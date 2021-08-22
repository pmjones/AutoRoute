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

class Generator
{
    public function __construct(
        protected Actions $actions,
        protected Filter $filter,
    ) {
    }

    public function generate(string $class, mixed ...$values) : string
    {
        $reverse = $this->actions->getReverse($class);
        $path = $reverse->path;
        $count = count($values);

        $pairs = [];
        $parameters = $reverse->parameters;
        $i = 0;

        while (! empty($parameters)) {
            $rp = array_shift($parameters);

            if ($rp->isVariadic()) {
                while (! empty($values)) {
                    $path .= $this->segments($rp, $values);
                }

                break;
            }

            $segments = $this->segments($rp, $values);

            if ($rp->isOptional()) {
                $path .= $segments;
            } else {
                $pairs['{' . $i . '}'] = ltrim($segments, '/');
                $i ++;
            }
        }

        if (! empty($values)) {
            throw new Exception\NotFound(
                "Too many arguments provided for {$class}"
            );
        }

        $path = strtr($path, $pairs);
        return '/' . trim($path, '/');
    }

    protected function segments(
        ReflectionParameter $rp,
        array &$values
    ) : string
    {
        if (empty($values) && $rp->isOptional()) {
            return '';
        }

        $original = $values;

        // do not capture the filtered value, just validate it
        $this->filter->parameter($rp, $values);

        // capture the original values
        $segments = [];
        $k = count($original) - count($values);
        for ($i = 0; $i < $k; $i ++) {
            $value = array_shift($original);

            // only arrays cannot be cast to string
            if (is_array($value)) {
                $value = implode(',', $value);
            }

            // cast to a string for the url path
            $segments[] = (string) $value;
        }

        return '/' . implode('/', $segments);
    }
}
