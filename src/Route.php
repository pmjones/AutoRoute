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

use JsonSerializable;
use Throwable;

/**
 * @property-read string $class
 * @property-read string $method
 * @property-read array $arguments
 * @property-read ?string $error
 * @property-read ?Throwable $exception
 * @property-read array $headers
 * @property-read array $messages
 */
class Route implements JsonSerializable
{
    public function __construct(
        protected string $class,
        protected string $method,
        protected array $arguments,
        protected ?string $error = null,
        protected ?Throwable $exception = null,
        protected array $headers = [],
        protected array $messages = [],
    ) {
    }

    public function __get(string $key) : mixed
    {
        return $this->$key;
    }

    public function asArray() : array
    {
        return get_object_vars($this);
    }

    public function jsonSerialize() : mixed
    {
        return get_object_vars($this);
    }
}
