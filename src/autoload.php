<?php

require_once __DIR__ . '/shared/core/function/polyfill.php';
require_once __DIR__ . '/shared/core/function/calendar.php';
require_once __DIR__ . '/shared/core/function/error.php';
require_once __DIR__ . '/shared/core/function/gFile.php';
require_once __DIR__ . '/shared/core/function/text.php';
require_once __DIR__ . '/shared/core/function/util.php';

require_once __DIR__ . '/shared/fastroute/src/functions.php';


spl_autoload_register(function($className) {
    $className = ltrim($className, '\\');
    $classes   = explode('\\', $className);

    if ($classes[0] == 'Galaxia') {
        switch ($classes[1]) {
            case 'FastRoute':
                $fileName = __DIR__ . '/shared/fastroute/src/' . implode('/', array_slice($classes, 2)) . '.php';
                break;

            case 'RedisCli':
                $fileName = __DIR__ . '/shared/redis/src/RedisCli.php';
                break;

            default:
                $fileName = __DIR__ . '/shared/core/class/' . implode('/', array_slice($classes, 1)) . '.php';
                break;
        }
        require_once $fileName;
    }
});
