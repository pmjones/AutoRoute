<?php
use AutoRoute\AutoRoute;

$autoload = require dirname(__DIR__) . '/vendor/autoload.php';

$options = getopt('', ['method:', 'suffix:', 'template:', 'word-separator:'], $optind);

$namespace = $argv[$optind + 0] ?? null;
if ($namespace === null) {
    echo "Please pass a PHP namespace as the first argument." . PHP_EOL;
    exit(1);
}

$directory = realpath($argv[$optind + 1] ?? null);
if ($directory === false) {
    echo "Please pass the PHP namespace directory path as the second argument." . PHP_EOL;
    exit(1);
}

$verb = $argv[$optind + 2] ?? null;
if ($verb === null) {
    echo "Please pass an HTTP verb as the third argument." . PHP_EOL;
    exit(1);
}

$path = $argv[$optind + 3] ?? null;
if ($path === null) {
    echo "Please pass a URL path as the fourth argument." . PHP_EOL;
    exit(1);
}

$template = $options['template'] ?? dirname(__DIR__) . '/resources/templates/action.tpl';
if (! file_exists($template)) {
    echo "Template file {$template} does not exist." . PHP_EOL;
    exit(1);
}

// ---

$autoRoute = (new AutoRoute($namespace, $directory))
    ->setMethod($options['method'] ?? '__invoke')
    ->setSuffix($options['suffix'] ?? '')
    ->setWordSeparator($options['word-separator'] ?? '-');

$creator = $autoRoute->newCreator(file_get_contents($template));

[$file, $code] = $creator->create($verb, $path);

echo $file . PHP_EOL;
if (file_exists($file)) {
    echo "Already exists; not overwriting." . PHP_EOL;
    exit(1);
}

$dir = dirname($file);
if (! is_dir($dir)) {
    mkdir($dir, 0777, true);
}

file_put_contents($file, $code);

exit(0);
