<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\Flash;
use Galaxia\G;



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

    if (G::isCli()) {
        foreach ($backtrace as $trace) {
            echo ($trace['file'] ?? $trace['function'] ?? '??') . ':' . ($trace['line'] ?? $trace['args'][0] ?? '??') . PHP_EOL;
        }
        d(func_get_args());
        return;
    }


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
