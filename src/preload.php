<?php

$directory = new RecursiveDirectoryIterator(__DIR__ . '/Galaxia');
$fullTree = new RecursiveIteratorIterator($directory);
$phpFiles = new RegexIterator($fullTree, '/.+((?<!Test)+\.php$)/i', RecursiveRegexIterator::GET_MATCH);

include __DIR__ . '/autoload.php';
include __DIR__ . '/Galaxia/polyfill.php';
include __DIR__ . '/Galaxia/fastroute/src/functions.php';

foreach ($phpFiles as $file) {
    if (str_ends_with($file[0], '/redis/test/commands.php')) continue;
    if (str_ends_with($file[0], '/fastroute/src/functions.php')) continue;

    require_once $file[0];
    // opcache_compile_file($file[0]);
}
