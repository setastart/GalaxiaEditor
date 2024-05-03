<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\RedisCli;

include_once dirname(__DIR__) . '/src/boot-cli-editor.php';


$redis = new RedisCli(host: 'localhost', port: '6379');


function exitWithError($msg): never {
    echo PHP_EOL . PHP_EOL;
    echo("️🛑 Error: " . $msg . PHP_EOL);
    echo PHP_EOL . PHP_EOL;
    exit(1);
}


if (is_resource($redis->handle)) {
    $redis->setErrorFunction(function($error) {
        exitWithError('RedisCli: ' . __METHOD__ . ':' . __LINE__ . ' ' . $error);
    });
    if ($redis->cmd('PING')->get() != 'PONG') {
        exitWithError('RedisCli: PING does not respont PONG');
    }
} else {
    exitWithError('Redis: Connection Failed');
}


$in = dirname(__DIR__) . '/public/edit/favicon.png';

$vips = vips_image_new_from_file($in)['out'] ?? false;
if (!$vips) {
    exitWithError('Vips: Could not load vips image.');
}

$loader = vips_image_get($vips, 'vips-loader')['out'] ?? false;
if (!$loader) exitWithError('Could not load vips file loader.');

$ext = ['jpegload' => '.jpg', 'pngload' => '.png'] ?? false;
if (!$ext) exitWithError('Could not load vips file format.');

$w = vips_image_get($vips, 'width')['out'] ?? 0;
$h = vips_image_get($vips, 'height')['out'] ?? 0;
if (!$w || !$h) exitWithError('Could not read vips image dimensions.');
