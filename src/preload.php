<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

$directory = new RecursiveDirectoryIterator(__DIR__ . '/Galaxia');
$fullTree = new RecursiveIteratorIterator($directory);
$phpFiles = new RegexIterator($fullTree, '/.+((?<!Test)+\.php$)/i', RegexIterator::GET_MATCH);

include __DIR__ . '/autoload.php';
include __DIR__ . '/Galaxia/fastroute/src/functions.php';

foreach ($phpFiles as $file) {
    if (str_ends_with($file[0], '/redis/test/commands.php')) continue;
    if (str_ends_with($file[0], '/fastroute/src/functions.php')) continue;
    if (str_ends_with($file[0], '.stub.php')) continue;

    require_once $file[0];
    // opcache_compile_file($file[0]);
}
