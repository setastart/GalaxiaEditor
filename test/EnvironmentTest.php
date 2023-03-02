<?php

use Galaxia\RedisCli;

include_once dirname(__DIR__) . '/src/boot-cli-editor.php';


$redis = new RedisCli(host: 'localhost', port: '6379');


function exitWithError($msg): never {
    echo("️🛑 Error: " . $msg . PHP_EOL);
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


$in = dirname(__DIR__) . '/public/edit/favicon.pnq';
$vips = vips_image_new_from_file($in)['out'] ?? false;
if (!$vips) {
    exitWithError('Vips: Could not load vips image.');
}
