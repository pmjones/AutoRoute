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

use Psr\Log\LoggerInterface;

class AutoRoute
{
    protected ?Actions $actions = null;

    protected Config $config;

    protected ?Creator $creator = null;

    protected ?Dumper $dumper = null;

    protected ?Filter $filter = null;

    protected ?Generator $generator = null;

    protected mixed /* callable */ $loggerFactory = null;

    protected ?Logger $logger = null;

    protected ?Reflector $reflector = null;

    protected ?Router $router = null;

    public function __construct(
        string $namespace,
        string $directory,
        string $baseUrl = '/',
        int $ignoreParams = 0,
        string $method = '__invoke',
        string $suffix = '',
        string $wordSeparator = '-',
        mixed /* callable */ $loggerFactory = null,
    ) {
        $this->config = new Config(
            $namespace,
            $directory,
            $baseUrl,
            $ignoreParams,
            $method,
            $suffix,
            $wordSeparator,
        );

        if ($loggerFactory === null) {
            $loggerFactory = function () : LoggerInterface {
                return new Logger();
            };
        }

        $this->loggerFactory = $loggerFactory;
    }

    public function getActions() : Actions
    {
        if ($this->actions === null) {
            $this->actions = new Actions(
                $this->getConfig(),
                $this->getReflector(),
            );
        }

        return $this->actions;
    }

    public function getConfig() : Config
    {
        return $this->config;
    }

    public function getCreator() : Creator
    {
        if ($this->creator === null) {
            $this->creator = new Creator($this->getConfig());
        }

        return $this->creator;
    }

    public function getDumper() : Dumper
    {
        if ($this->dumper === null) {
            $this->dumper = new Dumper(
                $this->getConfig(),
                $this->getReflector(),
                $this->getActions(),
            );
        }

        return $this->dumper;
    }

    public function getFilter() : Filter
    {
        if ($this->filter === null) {
            $this->filter = new Filter($this->getReflector());
        }

        return $this->filter;
    }

    public function getGenerator() : Generator
    {
        if ($this->generator === null) {
            $this->generator = new Generator(
                $this->getActions(),
                $this->getFilter()
            );
        }

        return $this->generator;
    }

    public function getLogger() : LoggerInterface
    {
        if ($this->logger === null) {
            $this->logger = ($this->loggerFactory)();
        }

        return $this->logger;
    }

    public function getReflector() : Reflector
    {
        if ($this->reflector === null) {
            $this->reflector = new Reflector($this->getConfig());
        }

        return $this->reflector;
    }

    public function getRouter() : Router
    {
        if ($this->router === null) {
            $this->router = new Router(
                $this->getConfig(),
                $this->getActions(),
                $this->getFilter(),
                $this->getLogger(),
            );
        }

        return $this->router;
    }
}
