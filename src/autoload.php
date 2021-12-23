<?php

use Galaxia\G;


spl_autoload_register(function($className) {
    $className = ltrim($className, '\\');
    $classes   = explode('\\', $className);

    if ($classes[0] == 'Galaxia') {
        switch ($classes[1]) {
            case 'FastRoute':
                $fileName = __DIR__ . '/Galaxia/fastroute/src/' . implode('/', array_slice($classes, 2)) . '.php';
                break;

            case 'PHPMailer':
                $fileName = __DIR__ . '/Galaxia/mailer/' . implode('/', array_slice($classes, 2)) . '.php';
                break;

            case 'RedisCli':
                $fileName = __DIR__ . '/Galaxia/redis/src/RedisCli.php';
                break;

            default:
                $fileName = __DIR__ . '/Galaxia/core/' . implode('/', array_slice($classes, 1)) . '.php';
                break;
        }
        require_once $fileName;
    }
});

require_once __DIR__ . '/Galaxia/polyfill.php';
require_once __DIR__ . '/Galaxia/fastroute/src/functions.php';



// todo: improve
// @formatter:off
if (G::isDevEnv()) {
    function d(...$vars) {
        foreach ($vars as $var) {
            ob_start();
            var_dump($var);
            $dump = ob_get_clean();
            $dump = preg_replace('/=>\n\s+/m', ' => ', (string)$dump);
            $dump = str_replace('<?php ', '', (string)$dump);
            echo $dump;
        }
    }
    function s(...$vars) { d($vars); }
    function dd(...$vars) { d($vars); exit; }
    function db() {
        ob_start();
        debug_print_backtrace();
        $trace = ob_get_clean();
        $trace = str_replace(G::dir(), '', $trace);
        print $trace;
        return;
    }
} else if (G::isCli() || G::isDevDebug()) {
    function d(...$vars) {
        foreach ($vars as $var) {
            ob_start();
            var_dump($var);
            $dump = ob_get_clean();
            $dump = preg_replace('/=>\n\s+/m', ' => ', (string)$dump);
            $dump = str_replace('<?php ', '', (string)$dump);
            echo $dump . PHP_EOL;
        }
    }
    function s(...$vars) { d($vars); }
    function dd(...$vars) { d($vars); exit; }
    function db() {
        $backtrace = array_reverse(debug_backtrace());
        $r = '';
        foreach ($backtrace as $trace) {
            foreach (['file', 'class', 'function', 'line', 'type'] as $property) {
                if ($trace[$property] ?? '') $r .= ' - ' . $trace[$property];
            }
            $r .= PHP_EOL;
        }
        echo $r . PHP_EOL;
    }
} else {
    function d() {}
    function s() {}
    function dd() {}
    function db() {}
}
// @formatter:on



