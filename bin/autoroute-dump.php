<?php
use AutoRoute\AutoRoute;

$autoload = require dirname(__DIR__) . '/vendor/autoload.php';

$options = getopt('', ['base-url:', 'ignore-params:', 'method:', 'suffix:', 'word-separator:'], $optind);

$namespace = $argv[$optind + 0] ?? null;
if ($namespace === null) {
    echo "Please pass a PHP namespace as the first argument.";
    exit(1);
}

$directory = realpath($argv[$optind + 1] ?? null);
if ($directory === false) {
    echo "Please pass the PHP namespace directory path as the second argument.";
    exit(1);
}

$namespace = rtrim($namespace, '\\') . '\\';
$autoload->addPsr4($namespace, $directory);

$autoRoute = (new AutoRoute($namespace, $directory))
    ->setBaseUrl($options['base-url'] ?? '/')
    ->setIgnoreParams((int) $options['ignore-params'] ?? 0)
    ->setMethod($options['method'] ?? '__invoke')
    ->setSuffix($options['suffix'] ?? '')
    ->setWordSeparator($options['word-separator'] ?? '-');

// ---

$dumper = $autoRoute->newDumper();

try {
    $urls = $dumper->dumpRoutes();
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}

foreach ($urls as $path => $info) {
    foreach ($info as $verb => $class) {
        $verb = str_pad(strtoupper($verb), 7);
        echo "$verb $path" . PHP_EOL;
        echo "        $class" . PHP_EOL;
    }
}

exit(0);
