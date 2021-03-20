<?php

use Galaxia\Director;


spl_autoload_register(function($className) {
    $className = ltrim($className, '\\');
    $classes   = explode('\\', $className);

    if ($classes[0] == 'Galaxia') {
        $fileName = match ($classes[1]) {
            'FastRoute' => __DIR__ . '/Galaxia/fastroute/src/' . implode('/', array_slice($classes, 2)) . '.php',
            'PHPMailer' => __DIR__ . '/Galaxia/mailer/' . implode('/', array_slice($classes, 2)) . '.php',
            'RedisCli' => __DIR__ . '/Galaxia/redis/src/RedisCli.php',
            default => __DIR__ . '/Galaxia/core/' . implode('/', array_slice($classes, 1)) . '.php',
        };
        require_once $fileName;
    }
});

require_once __DIR__ . '/Galaxia/polyfill.php';
require_once __DIR__ . '/Galaxia/fastroute/src/functions.php';




// @formatter:off
if (Director::isDevEnv()) {
    include_once __DIR__ . '/Galaxia/kint.phar';
    Kint\Renderer\RichRenderer::$folder = false;
    function dd(...$vars) {
        +!Kint::dump(...$vars);
        exit;
    }
    function db() {
        +!Kint::trace();
    }
} else if (Director::isCli() || Director::isDevDebug()) {
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
        $backtrace = array_reverse(debug_backtrace());
        $r = '';
        foreach ($backtrace as $trace) {
            foreach (['file', 'class', 'function', 'line', 'type'] as $property) {
                if ($trace[$property] ?? '') $r .= ' - ' . $trace[$property];
            }
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



