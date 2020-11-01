<?php
/* Copyright 2017-2020 Ino Detelić

 - Licensed under the EUPL, Version 1.2 only (the "Licence");
 - You may not use this work except in compliance with the Licence.

 - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

 - Unless required by applicable law or agreed to in writing, software distributed
   under the Licence is distributed on an "AS IS" basis,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 - See the Licence for the specific language governing permissions and limitations under the Licence.
*/



function slope(int $x1, int $y1, int $x2, int $y2): float {
    return ($y2 - $y1) / ($x2 - $x1);
}


/**
 * Get the y-intersect of two points
 * calc slope:       m = (y2 - y1) / (x2 - x1)
 * calc y-intersect: b = y1 - (m * x1)
 */
function yIntersect(int $y1, int $y2, int $x1 = 320, int $x2 = 1000, int $round = 3): float {
    $m = ($y2 - $y1) / ($x2 - $x1);

    return round($y1 - ($m * $x1), $round, PHP_ROUND_HALF_UP);
}




// debug
function d() {
    foreach (func_get_args() as $arg) {
        ob_start();
        var_dump($arg);
        $dump = ob_get_clean();
        $dump = preg_replace('/=>\n\s+/m', ' => ', (string)$dump);
        $dump = str_replace('<?php ', '', (string)$dump);
        echo $dump;
    }
}

// debug with <pre>
function dp() {
    $args = func_get_args();
    echo '<pre>';
    call_user_func_array('d', $args);
    echo '</pre>';
}

// debug and die
function dd() {
    $args = func_get_args();
    call_user_func_array('d', $args);
    exit();
}

// debug and die with <pre>
function ddp() {
    $args = func_get_args();
    echo '<pre>';
    call_user_func_array('d', $args);
    echo '</pre>';
    exit();
}

// debug backtrace
function db() {
    $args      = func_get_args();
    $backtrace = array_reverse(debug_backtrace());
    $error     = '';
    $prefix    = '<span class="select-on-click">';
    $suffix    = '</span><br>';
    if (php_sapi_name() == 'cli') {
        $prefix    = '';
        $suffix    = PHP_EOL;
    }
    foreach ($backtrace as $trace) {
        if (empty($trace)) continue;
        $error .= $prefix . $trace['file'] . ':' . $trace['line'] . $suffix;
    }
    echo $error;
    if (php_sapi_name() != 'cli') echo '<pre>';
    call_user_func_array('d', $args);
    if (php_sapi_name() != 'cli') echo '</pre>';
}
