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

class Actions
{
    protected array $instances = [];

    protected array $reversals = [];

    protected Reverser $reverser;

    public function __construct(
        protected Config $config,
        protected Reflector $reflector,
    ) {
        $this->reverser = new Reverser($this->config, $this);
    }

    public function getAction(string $class) : Action
    {
        if (! isset($this->instances[$class])) {
            $this->instances[$class] = $this->newAction($class);
        }

        return $this->instances[$class];
    }

    protected function newAction(string $class) : Action
    {
        $ns = substr($class, 0, $this->config->namespaceLen);

        if ($ns !== $this->config->namespace) {
            throw new Exception\InvalidNamespace("Expected namespace {$this->config->namespace}, actually {$class}");
        }

        if (! $this->reflector->classExists($class)) {
            throw new Exception\NotFound("Expected class {$class}, actually not found");
        }

        $parameters = $this->reflector->getActionParameters($class);

        for ($i = 0; $i < $this->config->ignoreParams; $i ++) {
            array_shift($parameters);
        }

        $requiredParameters = [];
        $optionalParameters = [];

        foreach ($parameters as $i => $rp) {
            if ($rp->isOptional()) {
                $optionalParameters[$i] = $rp;
            } else {
                $requiredParameters[$i] = $rp;
            }
        }

        return new Action(
            $class,
            $requiredParameters,
            $optionalParameters
        );
    }

    public function getReverse(string $class) : Reverse
    {
        if (! isset($this->reversals[$class])) {
            $this->reversals[$class] = $this->reverser->reverse($class);
        }

        return $this->reversals[$class];
    }

    public function getClass(
        string $verb,
        string $subNamespace,
        string $tail = ''
    ) : ?string
    {
        $base = rtrim($this->config->namespace, '\\')
            . $subNamespace
            . '\\';

        if ($tail !== '') {
            $base .= $tail . '\\';
        }

        $ending = str_replace('\\', '', $subNamespace . $tail) . $this->config->suffix;
        return $base . $verb . $ending;
    }

    public function hasAction(
        string $verb,
        string $subNamespace,
        string $tail = ''
    ) : ?string
    {
        $class = $this->getClass($verb, $subNamespace, $tail);

        if ($this->reflector->classExists($class)) {
            return $class;
        }

        if ($verb !== 'Head') {
            return null;
        }

        $class = $this->getClass('Get', $subNamespace, $tail);
        return $this->reflector->classExists($class) ? $class : null;
    }
}
