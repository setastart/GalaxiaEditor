<?php

use Galaxia\Flash;



spl_autoload_register(function($className) {
    $className = ltrim($className, '\\');
    $classes   = explode('\\', $className);

    if ($classes[0] == 'GalaxiaEditor') {
        $fileName = __DIR__ . '/GalaxiaEditor/' . implode('/', array_slice($classes, 1)) . '.php';
        require_once $fileName;
    }
});


function geD(): void {
    $dump      = '';
    $backtrace = array_reverse(debug_backtrace());

    foreach ($backtrace as $trace) {
        $dump .= '<span class="select-on-click">' . ($trace['file'] ?? $trace['function'] ?? '??') . ':' . ($trace['line'] ?? $trace['args'][0] ?? '??') . '</span><br>';
    }
    foreach (func_get_args() as $arg) {
        ob_start();
        s($arg);
        $dumpTemp = ob_get_clean();
        $dumpTemp = highlight_string('<?php ' . $dumpTemp, true);
        $dumpTemp = str_replace('&lt;?php&nbsp;', '', $dumpTemp);
        $dump     .= $dumpTemp . PHP_EOL;
    }
    Flash::devlog($dump);
}
