<?php

use Galaxia\{Director, Authentication};


// composer

if (file_exists(dirname(dirname(__DIR__)) . '/_galaxiaComposer/vendor/autoload.php')) {
    require dirname(dirname(__DIR__)) . '/_galaxiaComposer/vendor/autoload.php';
} else if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require dirname(__DIR__) . '/vendor/autoload.php';
} else {
    http_response_code(500);
    $title = 'Error 500: Internal Server Error';
    echo '<!doctype html><meta charset=utf-8><title>' . $title . '</title><body style="font-family: monospace;"><p style="font-size: 1.3em; margin-top: 4em; text-align: center;">' . $title . '</p><!-- error: autoloader not found -->' . PHP_EOL;
    exit();
}




// editor autoloader

spl_autoload_register(function ($className) {
    $className = ltrim($className, '\\');
    $fileName  = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $ext = '.php';
    if (substr($className, 0, 4) == 'View') $ext = '.phtml';
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . $ext;
    // if (Director::$dev && !file_exists($fileName)) db();
    require_once $fileName;
});




// requires

require __DIR__ . '/function/inputRender.php';
require __DIR__ . '/function/paginationRender.php';
require __DIR__ . '/function/utils.php';
