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

/**
 * @property-read string $class
 * @property-read string $verb
 * @property-read string $path
 * @property-read array $parameters
 * @property-read int $requiredParametersTotal
 */
class Reverse
{
    public function __construct(
        protected string $class,
        protected string $verb,
        protected string $path,
        protected array $parameters,
        protected int $requiredParametersTotal,
    ) {
    }

    public function __get(string $key) : mixed
    {
        return $this->$key;
    }
}
