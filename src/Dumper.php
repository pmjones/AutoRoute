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

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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
        $files = $this->getFiles();
        $classes = $this->getClassesFromFiles($files);
        $urls = $this->getUrlsFromClasses($classes);
        return $urls;
    }

    protected function getFiles() : array
    {
        $files = [];

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->config->directory
            )
        );

        foreach ($items as $item) {
            $file = $item->getPathname();

            if (substr($file, -4) == '.php') {
                $files[] = $file;
            }
        }

        return $files;
    }

    protected function getClassesFromFiles(array $files) : array
    {
        $classes = [];

        foreach ($files as $file) {
            $class = $this->fileToClass($file);

            if ($class !== null) {
                $classes[] = $class;
            }
        }

        sort($classes);
        return $classes;
    }

    protected function getUrlsFromClasses(array $classes) : array
    {
        $urls = [];

        foreach ($classes as $class) {
            $reverse = $this->actions->getReverse($class);
            $path = $this->namedPath($reverse);
            $urls[$path][$reverse->verb] = $class;

            if ($reverse->verb === 'Get' && ! isset($urls[$path]['Head'])) {
                $urls[$path]['Head'] = $class;
            }
        }

        ksort($urls);
        return $urls;
    }

    protected function fileToClass(string $file) : ?string
    {
        $file = str_replace($this->config->directory . DIRECTORY_SEPARATOR, '', substr($file, 0, -4));
        $parts = explode(DIRECTORY_SEPARATOR, $file);
        $last = array_pop($parts);
        $core = implode('', $parts);
        $verb = substr($last, 0, strlen($last) - strlen($core) - $this->config->suffixLen);

        if ($verb === '') {
            return null;
        }

        $subNamespace = '';

        if (! empty($parts)) {
            $subNamespace = '\\' . implode('\\', $parts);
        }

        return $this->actions->hasAction($verb, $subNamespace);
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

        if (! $this->reflector->classExists($type)) {
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
