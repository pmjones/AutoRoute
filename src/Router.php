<?php
declare(strict_types=1);

namespace AutoRoute;

class Router
{
    protected $actions;

    protected $action;

    protected $verb;

    protected $segments;

    protected $subNamespace;

    protected $class;

    protected $dynamic;

    protected $filter;

    public function __construct(Actions $actions)
    {
        $this->actions = $actions;
        $this->filter = new Filter();
    }

    public function route(string $verb, string $path) : Route
    {
        $originalPath = $path;

        $this->verb = ucfirst(strtolower($verb));
        $this->segments = $this->actions->getSegments($path);
        $this->subNamespace = '';
        $this->class = '';
        $this->action = null;
        $this->dynamic = [];

        do {
            $this->match();
        } while (! empty($this->segments));

        if (! class_exists($this->class)) {
            $verb = strtoupper($verb);
            $ns = rtrim($this->actions->getNamespace(), '\\') . $this->subNamespace;
            throw new MethodNotAllowed("$verb action not found in namespace $ns");
        }

        $action = $this->actions->getAction($this->class);
        $params = $this->filter($action->getParameters());
        return new Route($action->getClass(), $action->getMethod(), $params);
    }

    protected function match() : void
    {
        // consume next segment as a subnamespace
        $segment = '';
        if (! empty($this->segments)) {
            $segment = $this->actions->segmentToNamespace(array_shift($this->segments));
            $this->subNamespace .= '\\' . $segment;
        }

        // does the subnamespace exist?
        if (! $this->actions->isSubNamespace($this->subNamespace)) {
            // no, so no need to keep matching
            $ns = rtrim($this->actions->getNamespace(), '\\') . $this->subNamespace;
            throw new InvalidNamespace("Not a known namespace: $ns");
        }

        // does the class exist?
        $class = $this->actions->actionExists($this->verb, $this->subNamespace);
        if ($class === null) {
            // consume next segment as a namespace
            return;
        }

        $this->class = $class;
        $this->matchStaticTailSegment();
        $this->matchRequiredSegments();
        $this->matchOptionalSegments();
    }

    protected function matchStaticTailSegment() : void
    {
        if (count($this->segments) !== 1) {
            return;
        }

        $segment = $this->actions->segmentToNamespace($this->segments[0]);
        $temp = $this->actions->actionExists($this->verb, $this->subNamespace, $segment);
        if ($temp !== null) {
            array_shift($this->segments);
            $this->subNamespace .= '\\' . $segment;
            $this->class = $temp;
        }
    }

    protected function matchRequiredSegments()
    {
        // reflect on the action method
        $this->action = $this->actions->getAction($this->class);

        // consume one segment per required param, minus any dynamic segments
        // we have already captured
        $required = $this->action->getRequired() - count($this->dynamic);

        if (count($this->segments) < $required) {
            $method = $this->actions->getMethod();
            throw new NotFound("Not enough segments for {$this->class}::{$method}().");
        }

        while ($required > 0) {
            $this->dynamic[] = array_shift($this->segments);
            $required --;
        }
    }

    protected function matchOptionalSegments()
    {
        if (! $this->action->hasOptionals() || empty($this->segments)) {
            // no optionals, or no segments to fulfill them
            return;
        }

        // is the segment a subnamespace?
        $segment = $this->actions->segmentToNamespace($this->segments[0]);
        $temp = $this->subNamespace . '\\' . $segment;
        if ($this->actions->isSubNamespace($temp)) {
            // yes, consume it as a subnamespace instead
            return;
        }

        // the segment is not a subnamespace; further routing to subnamespaces
        // is terminated. consume optional params ...
        $optional = $this->action->getOptional();
        while ($optional > 0 && ! empty($this->segments)) {
            $this->dynamic[] = array_shift($this->segments);
            $optional --;
        }

        // ... and variadic params.
        while ($this->action->hasVariadic() && ! empty($this->segments)) {
            $this->dynamic[] = array_shift($this->segments);
        }

        // routing is terminated; there cannot be any segments remaining.
        if (! empty($this->segments)) {
            $class = $this->action->getClass();
            $method = $this->action->getMethod();
            throw new NotFound("Too many router segments for {$class}::{$method}()");
        }
    }

    protected function filter(array $parameters)
    {
        $input = $this->dynamic;
        $output = [];
        $filter = new Filter();

        while (! empty($input)) {
            $rp = array_shift($parameters);

            // non-variadic values
            if (! $rp->isVariadic()) {
                $output[] = $filter->forAction($rp, array_shift($input));
                continue;
            }

            // all remaining values as variadic
            while (! empty($input)) {
                $value = array_shift($input);
                $output[] = $filter->forAction($rp, $value);
            }
        }

        return $output;
    }
}
