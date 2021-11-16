<?php

require_once __DIR__ . '/GalaxiaEditor/function/utils.php';


spl_autoload_register(function($className) {
    $className = ltrim($className, '\\');
    $classes   = explode('\\', $className);

    if ($classes[0] == 'GalaxiaEditor') {
        $fileName = __DIR__ . '/GalaxiaEditor/' . implode('/', array_slice($classes, 1)) . '.php';
        require_once $fileName;
    }
});


