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
 * @property-read string $namespace
 * @property-read int $namespaceLen
 * @property-read string $directory
 * @property-read string $baseUrl
 * @property-read int $baseUrlLen
 * @property-read int $ignoreParams
 * @property-read string $method
 * @property-read string $suffix
 * @property-read int $suffixLen
 * @property-read string $wordSeparator
 */
class Config
{
    protected int $baseUrlLen;

    protected int $namespaceLen;

    protected int $suffixLen;

    public function __construct(
        protected string $namespace,
        protected string $directory,
        protected string $baseUrl = '/',
        protected int $ignoreParams = 0,
        protected string $method = '__invoke',
        protected string $suffix = '',
        protected string $wordSeparator = '-',
    ) {
        $this->baseUrl = trim($this->baseUrl, '/');
        $this->baseUrlLen = strlen($this->baseUrl);
        $this->directory = rtrim($this->directory, DIRECTORY_SEPARATOR);
        $this->namespace = trim($this->namespace, '\\') . '\\';
        $this->namespaceLen = strlen($this->namespace);
        $this->suffixLen = strlen($this->suffix);
    }

    public function __get(string $key) : mixed
    {
        return $this->$key;
    }
}
