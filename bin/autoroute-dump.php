<?php
use AutoRoute\AutoRoute;

$autoload = '';

$autoloadFiles = [
    dirname(__DIR__) . '/vendor/autoload.php',
    dirname(dirname(dirname(__DIR__))) . '/autoload.php'
];
foreach ($autoloadFiles as $autoloadFile) {
    if (file_exists($autoloadFile)) {
        $autoload = require $autoloadFile;
        break;
    }
}

$options = getopt('', ['base-url:', 'ignore-params:', 'method:', 'suffix:', 'word-separator:'], $optind);

$namespace = $argv[$optind + 0] ?? null;
if ($namespace === null) {
    echo "Please pass a PHP namespace as the first argument." . PHP_EOL;
    exit(1);
}

$directory = $argv[$optind + 1] ?? null;
if ($directory === null) {
    echo "Please pass the PHP namespace directory path as the second argument." . PHP_EOL;
    exit(1);
}

$realpath = realpath($directory);
if ($realpath === false) {
    echo "Directory {$directory} not found." . PHP_EOL;
    exit(1);
}

$namespace = rtrim($namespace, '\\') . '\\';
$autoload->addPsr4($namespace, $realpath);

$autoRoute = new AutoRoute(
    namespace: $namespace,
    directory: $realpath,
    baseUrl: $options['base-url'] ?? '/',
    ignoreParams: (int) ($options['ignore-params'] ?? 0),
    method: $options['method'] ?? '__invoke',
    suffix: $options['suffix'] ?? '',
    wordSeparator: $options['word-separator'] ?? '-',
);

// ---

$dumper = $autoRoute->getDumper();

try {
    $urls = $dumper->dump();
} catch (Throwable $e) {
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
