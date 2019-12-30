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
    protected $namespace;

    protected $directory;

    protected $suffix;

    protected $method;

    protected $wordSeparator;

    protected $template;

    public function __construct(
        string $namespace,
        string $directory,
        string $suffix,
        string $method,
        string $wordSeparator,
        string $template
    ) {
        $this->namespace = trim($namespace, '\\') . '\\';
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
        $this->suffix = $suffix;
        $this->method = $method;
        $this->wordSeparator = $wordSeparator;
        $this->template = $template;
    }

    public function create(string $verb, string $path) : array
    {
        [$namespace, $directory, $class, $parameters] = $this->parse($verb, $path);
        $file = "{$directory}/{$class}.php";
        $vars = [
            '{NAMESPACE}' => $namespace,
            '{CLASS}' => $class,
            '{METHOD}' => $this->method,
            '{PARAMETERS}' => $parameters,
        ];
        $code = strtr($this->template, $vars);
        return [$file, $code];
    }

    protected function parse(string $verb, string $path) : array
    {
        $segments = explode('/', trim($path, '/'));
        $subNamespaces = [];
        $parameters = [];

        while (! empty($segments)) {
            $segment = array_shift($segments);

            if (substr($segment, 0, 1) == '{') {
                $parameters[] = '$' . trim($segment, '{} ');
                continue;
            }

            $segment = str_replace($this->wordSeparator, ' ', $segment);
            $segment = str_replace(' ', '', ucwords($segment));
            $subNamespaces[] = $segment;
        }

        $namespace = '';

        foreach ($subNamespaces as $subNamespace) {
            $namespace .= ucfirst(strtolower($subNamespace)) . '\\';
        }

        $namespace = rtrim($namespace, '\\');

        $class = ucfirst(strtolower($verb))
            . str_replace('\\', '', $namespace)
            . $this->suffix;

        $directory = $this->directory . DIRECTORY_SEPARATOR
            . str_replace('\\', DIRECTORY_SEPARATOR, $namespace);

        return [
            rtrim($this->namespace . $namespace, '\\'),
            $directory,
            $class,
            implode(', ', $parameters)
        ];
    }
}
