<?php
declare(strict_types=1);

namespace AutoRoute;

class Generator
{
    protected $actions;

    protected $filter;

    public function __construct(Actions $actions)
    {
        $this->actions = $actions;
        $this->filter = new Filter();
    }

    public function generate(string $class, ...$dynamic) : string
    {
        list ($verb, $path, $argc) = $this->actions->generate($class);

        $count = count($dynamic);
        if ($count < $argc) {
            $classMethod = $this->actions->getAction($class)->getClassMethod();
            throw new NotFound("Expected $argc required argument(s) for {$classMethod}, actually {$count}");
        }

        $pairs = [];
        $action = $this->actions->getAction($class);
        $parameters = $action->getParameters();

        $i = 0;
        while (! empty($dynamic) && ! empty($parameters)) {

            $rp = array_shift($parameters);

            if ($rp->isVariadic()) {
                while (! empty($dynamic)) {
                    $path .= '/' . $this->filter->forSegment($rp, array_shift($dynamic));
                }
                break;
            }

            $segment = $this->filter->forSegment($rp, array_shift($dynamic));
            if ($rp->isOptional()) {
                $path .= '/' . $segment;
            } else {
                $pairs['{' . $i . '}'] = $segment;
                $i ++;
            }
        }

        if (! empty($dynamic)) {
            $classMethod = $action->getClassMethod();
            throw new NotFound("Too many generator segments for {$classMethod}");
        }

        $path = strtr($path, $pairs);
        return '/' . ltrim($path, '/');
    }
}
