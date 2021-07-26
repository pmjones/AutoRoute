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

use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

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

    public function hasSubNamespace(string $subNamespace) : bool
    {
        if (strpos($subNamespace, '..') !== false) {
            throw new Exception\NotFound("Directory dots not allowed in segments");
        }

        $dir = $this->config->directory . str_replace('\\', DIRECTORY_SEPARATOR, $subNamespace);
        return is_dir($dir);
    }

    public function getAllowed(string $subNamespace) : array
    {
        $verbs = [];
        $class = $this->getClass('', $subNamespace);
        $parts = explode('\\', $class);
        $main = end($parts). '.php';
        $mainLen = -1 * strlen($main);
        $dir = $this->config->directory . str_replace('\\', DIRECTORY_SEPARATOR, $subNamespace);
        $items = new DirectoryIterator($dir);

        foreach ($items as $item) {
            $file = $item->getFilename();

            if (substr($file, -4) !== '.php') {
                continue;
            }

            $verb = substr($file, 0, $mainLen);

            if ($verb !== '') {
                $verbs[] = strtoupper($verb);
            }
        }

        if (in_array('GET', $verbs) && ! in_array('HEAD', $verbs)) {
            $verbs[] = 'HEAD';
        }

        sort($verbs);
        return $verbs;
    }

    public function getClasses() : array
    {
        $classes = [];

        $files = new RegexIterator(
            new RecursiveIteratorIterator(
                 new RecursiveDirectoryIterator(
                    $this->config->directory
                )
            ),
            '/^.*\.php$/',
            RecursiveRegexIterator::GET_MATCH
        );

        foreach ($files as $file) {
            $class = $this->fileToClass($file[0]);

            if ($class !== null) {
                $classes[] = $class;
            }
        }

        sort($classes);
        return $classes;
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

        return $this->hasAction($verb, $subNamespace);
    }
}
