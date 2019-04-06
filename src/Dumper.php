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

use ReflectionClass;

class Dumper
{
    protected $actions;

    public function __construct(Actions $actions)
    {
        $this->actions = $actions;
    }

    public function dumpRoutes() : array
    {
        $files = $this->getFiles();
        $classes = $this->getClassesFromFiles($files);
        $urls = $this->getUrlsFromClasses($classes);
        return $urls;
    }

    protected function getFiles() : array
    {
        $dirlen = strlen($this->actions->getDirectory());
        $dir = escapeshellarg($this->actions->getDirectory());
        $command = "find $dir -name '*.php'";
        exec($command, $files, $exit);
        return $files;
    }

    protected function getClassesFromFiles(array $files) : array
    {
        $classes = [];

        foreach ($files as $file) {
            $class = $this->actions->fileToClass($file);
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
            list ($verb, $path, $argc) = $this->actions->dump($class);
            $urls[$path][$verb] = $class;
        }
        ksort($urls);
        return $urls;
    }
}
