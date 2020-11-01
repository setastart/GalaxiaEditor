<?php

require __DIR__ . '/function/inputRender.php';
require __DIR__ . '/function/paginationRender.php';
require __DIR__ . '/function/utils.php';


spl_autoload_register(function($className) {
    $className = ltrim($className, '\\');
    $fileName  = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', '/', $namespace) . '/';
    }
    $ext = '.php';
    if (substr($className, 0, 4) == 'View') $ext = '.phtml';
    $fileName .= str_replace('_', '/', $className) . $ext;
    $fileName = __DIR__ . '/' . $fileName;

    if (file_exists($fileName)) {
        include_once $fileName;
    }
});


