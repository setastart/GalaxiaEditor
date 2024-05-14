<?php
declare(strict_types=1);

namespace Galaxia\FastRoute;

use Galaxia\AppTimer;
use LogicException;
use function fclose;
use function flock;
use function fopen;
use function function_exists;
use function fwrite;
use function is_array;
use function restore_error_handler;
use function set_error_handler;
use function var_export;
use const LOCK_EX;
use const LOCK_SH;

if (!function_exists('Galaxia\FastRoute\simpleDispatcher')) {

    function simpleDispatcher(callable $routeDefinitionCallback): Dispatcher {
        $routeCollector = new RouteCollector(
            new RouteParser(),
            new DataGenerator()
        );
        $routeDefinitionCallback($routeCollector);
        return new Dispatcher($routeCollector->getData());
    }

    /**
     * @param array<string, string> $options
     */
    function cachedDispatcher(
        callable $routeDefinitionCallback,
        array    $options = []
    ): Dispatcher {
        $bypass = $options['cacheDisabled'] ?? false;
        $file   = $options['cacheFile'] ?? '';

        if (!$file) {
            throw new LogicException('Must specify "file" option');
        }

        $dispatchDataFunction = function() use ($routeDefinitionCallback, $options, $file): array {
            $routeCollector = new RouteCollector(
                new RouteParser(),
                new DataGenerator()
            );
            $routeDefinitionCallback($routeCollector);
            return $routeCollector->getData();
        };

        if ($bypass) {
            AppTimer::start('FastRoute BYPASS');
            $dispatchData = $dispatchDataFunction();
            AppTimer::stop('FastRoute BYPASS');
            return new Dispatcher($dispatchData);
        }

        set_error_handler(function($type, $msg) use (&$error) { $error = $msg; });
        $fp          = null;
        $timerName   = "FastRoute";
        $timerRename = "FastRoute";
        AppTimer::start($timerName);
        $dispatchData = null;

        if ($fp = fopen($file, 'r')) {
            $timerRename .= ' HIT';
            if (flock($fp, LOCK_SH)) {
                $dispatchData = require $file;
                fclose($fp);
                if (!is_array($dispatchData)) {
                    $dispatchData = null;
                    $timerRename  .= ' FAIL invalid cache';
                }
            } else {
                $timerRename .= ' FAIL shared lock';
            }
        } else if ($fp = fopen($file, 'w')) {
            $timerRename .= ' MISS';
            if (flock($fp, LOCK_EX)) {
                $dispatchData = $dispatchDataFunction();
                if (fwrite($fp, '<?php return ' . var_export($dispatchData, true) . ';')) {
                    $timerRename .= ' WRITE OK';
                } else {
                    $timerRename .= ' WRITE FAIL';
                }
                fclose($fp);
            } else {
                $timerRename .= ' FAIL exclusive lock';
            }
        } else {
            $timerRename .= ' FAIL fopen';
        }

        if ($dispatchData === null) {
            $timerRename  .= ' COMPUTED FALLBACK';
            $dispatchData = $dispatchDataFunction();
            AppTimer::stop($timerName, rename: $timerRename);
            restore_error_handler();
        }
        AppTimer::stop($timerName, rename: $timerRename);
        restore_error_handler();
        return new Dispatcher($dispatchData);
    }
}
