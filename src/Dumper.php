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

class Dumper
{
    public function __construct(
        protected Config $config,
        protected Reflector $reflector,
        protected Actions $actions,
    ) {
    }

    public function dump() : array
    {
        $classes = $this->actions->getClasses();
        $urls = $this->getUrlsFromClasses($classes);
        return $urls;
    }

    protected function getUrlsFromClasses(array $classes) : array
    {
        $urls = [];

        foreach ($classes as $class) {
            $reverse = $this->actions->getReverse($class);
            $path = $this->namedPath($reverse);

            // hack to fix optional catchall param when no base url
            if (substr($path, 0, 4) === '/[/{') {
                $path = '/[{' . substr($path, 4);
            } elseif (substr($path, 0, 3) === '//{') {
                $path = '/{' . substr($path, 3);
            }

            $urls[$path][$reverse->verb] = $class;

            if ($reverse->verb === 'Get' && ! isset($urls[$path]['Head'])) {
                $urls[$path]['Head'] = $class;
            }
        }

        ksort($urls);
        return $urls;
    }

    protected function namedPath(Reverse $reverse) : string
    {
        $namedPath = $reverse->path;
        $pairs = [];

        foreach ($reverse->parameters as $pos => $rp) {
            $this->namedPathPairs($reverse, $pos, $rp, $namedPath, $pairs);
        }

        return strtr($namedPath, $pairs);
    }

    protected function namedPathPairs(
        Reverse $reverse,
        int $pos,
        ReflectionParameter $rp,
        string &$namedPath,
        array &$pairs
    ) : void
    {
        if ($pos < $reverse->requiredParametersTotal) {
            $key = '/{' . $pos . '}';
            $pairs[$key] = $this->namedPathTokens($rp);
        } else {
            $namedPath .= $this->namedPathTokens($rp);
        }
    }

    protected function namedPathTokens(ReflectionParameter $rp) : string
    {
        $type = $this->reflector->getParameterType($rp);

        if (! class_exists($type)) {
            $name = $rp->getName();
            $vari = $rp->isVariadic() ? '...' : '';
            $dump = '/{' . $vari . $type . ':' . $name . '}';
            return $rp->isOptional() ? "[{$dump}]" : $dump;
        }

        $tokens = [];
        $ctorParams = $this->reflector->getConstructorParameters($type);

        foreach ($ctorParams as $ctorParam) {
            $tokens[] = $this->namedPathTokens($ctorParam);
        }

        return implode('', $tokens);
    }
}
