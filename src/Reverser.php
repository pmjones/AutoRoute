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
use ReflectionNamedType;

class Reverser
{
    protected string $currClass;

    protected string $prevClass;

    protected array $parameters = [];

    protected string $verb;

    protected string $path;

    protected int $requiredParametersTotal;

    protected string $subNamespace;

    protected array $parts;

    public function __construct(
        protected Config $config,
        protected Actions $actions,
    ) {
    }

    public function reverse(string $class) : Reverse
    {
        $this->parts = explode(
            '\\',
            substr($class, $this->config->namespaceLen)
        );
        $last = array_pop($this->parts);
        $impl = implode('', $this->parts);
        $this->verb = substr(
            $last,
            0,
            strlen($last) - strlen($impl) - $this->config->suffixLen
        );
        $this->path = $this->config->baseUrl;
        $this->requiredParametersTotal = 0;
        $this->subNamespace = '';
        $this->currClass = '';
        $this->prevClass = '';

        while (! empty($this->parts)) {
            $this->reverseSegments();
        }

        $action = $this->actions->getAction($class);
        $parameters = $action->getRequiredParameters()
            + $action->getOptionalParameters();

        return new Reverse(
            $class,
            $this->verb,
            '/' . ltrim($this->path, '/'),
            $parameters,
            $this->requiredParametersTotal
        );
    }

    protected function reverseSegments() : void
    {
        $this->prevClass = $this->currClass;
        $part = array_shift($this->parts);
        $this->path .= '/' . $this->namespaceToSegment($part);
        $this->subNamespace .= '\\' . $part;
        $class = $this->actions->hasAction($this->verb, $this->subNamespace);

        if ($class === null) {
            return;
        }

        $staticTailSegment = $this->reverseStaticTailSegment();

        if ($staticTailSegment) {
            return;
        }

        $this->currClass = $class;
        $action = $this->actions->getAction($this->currClass);
        $this->reverseRequiredSegments($action);
    }

    protected function reverseStaticTailSegment() : bool
    {
        if (count($this->parts) !== 1) {
            return false;
        }

        $prevRequired = 0;

        if ($this->prevClass !== '') {
            $prevRequired = count($this->actions
                ->getAction($this->prevClass)
                ->getRequiredParameters()
            );
        }

        $nextClass = (string) $this->actions->hasAction(
            $this->verb,
            $this->subNamespace,
            $this->parts[0]
        );

        $nextRequired = count($this->actions
            ->getAction($nextClass)
            ->getRequiredParameters()
        );

        if ($prevRequired !== $nextRequired) {
            return false;
        }

        $part = array_shift($this->parts);
        $this->path .= '/' . $this->namespaceToSegment($part);
        return true;
    }

    protected function reverseRequiredSegments(Action $action) : void
    {
        $count = count($action->getRequiredParameters())
            - $this->requiredParametersTotal;

        while ($count > 0) {
            $this->path .= '/{' . $this->requiredParametersTotal . '}';
            $this->requiredParametersTotal ++;
            $count --;
        }
    }

    protected function namespaceToSegment(string $part) : string
    {
        $part = (string) preg_replace(
            '/([a-z])([A-Z])/',
            "\$1{$this->config->wordSeparator}\$2",
            trim($part)
        );
        return strtolower($part);
    }
}
