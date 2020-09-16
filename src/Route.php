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

/**
 * @property-read string $class
 * @property-read string $method
 * @property-read array $params
 */
class Route
{
    protected $class;

    protected $method;

    protected $params = [];

    public function __construct(
        string $class,
        string $method,
        array $params
    ) {
        $this->class = $class;
        $this->method = $method;
        $this->params = $params;
    }

    public function __get($key) // : mixed
    {
        return $this->$key;
    }
}
