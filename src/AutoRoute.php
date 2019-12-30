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

class AutoRoute
{
    protected $namespace;

    protected $directory;

    protected $baseUrl = '/';

    protected $ignoreParams = 0;

    protected $method = '__invoke';

    protected $suffix = '';

    protected $wordSeparator = '-';

    public function __construct(string $namespace, string $directory)
    {
        $this->namespace = $namespace;
        $this->directory = $directory;
    }

    public function setBaseUrl(string $baseUrl) : self
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    public function setIgnoreParams(int $ignoreParams) : self
    {
        $this->ignoreParams = $ignoreParams;
        return $this;
    }

    public function setMethod(string $method) : self
    {
        $this->method = $method;
        return $this;
    }

    public function setSuffix(string $suffix) : self
    {
        $this->suffix = $suffix;
        return $this;
    }

    public function setWordSeparator(string $wordSeparator) : self
    {
        $this->wordSeparator = $wordSeparator;
        return $this;
    }

    public function newRouter()
    {
        return new Router($this->newActions());
    }

    public function newGenerator()
    {
        return new Generator($this->newActions());
    }

    public function newDumper()
    {
        return new Dumper($this->newActions());
    }

    public function newCreator(string $template)
    {
        return new Creator(
            $this->namespace,
            $this->directory,
            $this->suffix,
            $this->method,
            $this->wordSeparator,
            $template
        );
    }

    protected function newActions()
    {
        return new Actions(
            $this->namespace,
            $this->directory,
            $this->suffix,
            $this->method,
            $this->ignoreParams,
            $this->baseUrl,
            $this->wordSeparator
        );
    }
}
