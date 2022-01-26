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

class Creator
{
    public function __construct(
        protected Config $config,
    ) {
    }

    public function create(string $verb, string $path, string $template) : array
    {
        $parsed = $this->parse($verb, $path);
        $file = "{$parsed['directory']}/{$parsed['class']}.php";
        $vars = [
            '{NAMESPACE}' => $parsed['namespace'],
            '{CLASS}' => $parsed['class'],
            '{METHOD}' => $parsed['method'],
            '{PARAMETERS}' => $parsed['parameters'],
        ];
        $code = strtr($template, $vars);
        return [$file, $code];
    }

    public function parse(string $verb, string $path) : array
    {
        $segments = explode('/', trim($path, '/'));
        $namespace = [];
        $parameters = [];

        while (! empty($segments)) {
            $segment = array_shift($segments);

            if (substr($segment, 0, 1) == '{') {
                $parameters[] = '$' . trim($segment, '{} ');
                continue;
            }

            $segment = str_replace($this->config->wordSeparator, ' ', $segment);
            $segment = str_replace(' ', '', ucwords($segment));
            $namespace[] = $segment;
        }

        $namespace = implode('\\', $namespace);

        $class = ucfirst(strtolower($verb))
            . str_replace('\\', '', $namespace)
            . $this->config->suffix;

        $directory = $this->config->directory . DIRECTORY_SEPARATOR
            . str_replace('\\', DIRECTORY_SEPARATOR, $namespace);

        return [
            'namespace' => rtrim($this->config->namespace . $namespace, '\\'),
            'directory' => $directory,
            'class' => $class,
            'method' => $this->config->method,
            'parameters' => implode(', ', $parameters)
        ];
    }
}
