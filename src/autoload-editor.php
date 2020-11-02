<?php

require __DIR__ . '/function/inputRender.php';
require __DIR__ . '/function/paginationRender.php';
require __DIR__ . '/function/utils.php';


spl_autoload_register(function($className) {
    $className = ltrim($className, '\\');
    $classes   = explode('\\', $className);

    if ($classes[0] == 'GalaxiaEditor') {
        $fileName = __DIR__ . '/' . implode('/', array_slice($classes, 1)) . '.php';
        require_once $fileName;
    }
});


