<?php

use Galaxia\{Director, Sql};



function prepareInput($input, $extras) {

    if ($input['type'] == 'select' && isset($input['geExtraOptions'])) {
        foreach ($input['geExtraOptions'] as $key => $val) {
            foreach ($val as $colKey => $colVals) {
                if (!isset($extras[$key])) continue;
                if (is_string($colVals)) $colVals = [$colVals];
                foreach ($extras[$key] as $option) {
                    $add = true;
                    if (isset($input['geExtraOptionHas'][$key])) {
                        foreach ($input['geExtraOptionHas'][$key] as $constraintKey => $constraintVals) {
                            if (is_string($constraintVals)) $constraintVals = [$constraintVals];
                            foreach ($constraintVals as $constraintVal) {
                                if ($option[$constraintKey] != $constraintVal) {
                                    $add = false;
                                }
                            }
                        }
                    }

                    $found        = [];
                    $optionColVal = [];
                    foreach ($colVals as $colVal) {
                        if (substr($colVal, -3, 1) == '_') {
                            $canonical = substr($colVal, 0, -2);
                            if (empty($option[$colVal])) continue;
                            if (in_array($canonical, $found)) continue;
                            $found[] = substr($colVal, 0, -2);
                        }

                        $optionColVal[] = unsafet($option[$colVal]);
                    }
                    $optionColVal = array_filter($optionColVal);

                    if ($add) $input['options'][$option[$colKey]] = ['label' => implode(' / ', $optionColVal)];
                }
            }
        }
    }

    if (isset($input['options']['prefill'])) {
        switch ($input['options']['prefill']) {
            case 'week':
                $todayDt = new \DateTime();
                $afterDt = new \DateTime();
                $afterDt->modify('+1 week');
                $today          = $todayDt->format('Y-m-d');
                $after          = $afterDt->format('Y-m-d');
                $input['value'] = substr($today, 0, strspn($today ^ $after, "\0"));
                break;

            case 'day':
                $todayDt        = new \DateTime();
                $input['value'] = $todayDt->format('Y-m-d');
                break;

            default:
                break;
        }
    }

    return $input;
}




function insertHistory($uniqueId, $tabName, $tabId, $inputKey, $fieldKey, $action, $content, $userId) {
    // $action == 0: delete
    // $action == 1: save
    // $action == 2: update
    // $action == 3: create

    if ($action == 3 || $action == 0)
        if ($content == '') return null;

    if ($inputKey == 'passwordCurrent') return null;
    if ($inputKey == 'passwordRepeat') return null;
    if (substr($inputKey, 0, 8) == 'password') $content = '****************';

    $changes = [
        '_geUserId' => $userId,
        'uniqueId'  => $uniqueId,
        'action'    => $action,
        'tabName'   => $tabName,
        'tabId'     => $tabId,
        'fieldKey'  => $fieldKey,
        'inputKey'  => $inputKey,
        'content'   => $content,
    ];
    $values  = array_values($changes);
    $query   = Sql::queryInsert(['_geHistory' => ['_geUserId', 'uniqueId', 'action', 'tabName', 'tabId', 'fieldKey', 'inputKey', 'content']], $changes);
    try {
        $db    = Director::getMysqli();
        $stmt  = $db->prepare($query);
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        echo 'Unable to insert history: ' . $tabName . PHP_EOL;
        echo $e->getMessage() . PHP_EOL;

        return false;
    }

    return true;
}




// custom error reporting

function myErrorHandler($errNo, $errStr, $errFile, $errLine) {
    if (!(error_reporting() & $errNo)) {
        return false; // Run internal PHP error handler
    }

    switch ($errNo) {
        case E_PARSE:
        case E_ERROR:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
        case E_USER_ERROR:
            $errNo = 'Fatal Error';
            break;
        case E_WARNING:
        case E_USER_WARNING:
        case E_COMPILE_WARNING:
        case E_RECOVERABLE_ERROR:
            $errNo = 'Warning';
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
            $errNo = 'Notice';
            break;
        case E_STRICT:
            $errNo = 'Strict';
            break;
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            $errNo = 'Deprecated';
            break;
        default :
            break;
    }

    $backtrace = array_reverse(debug_backtrace());
    array_pop($backtrace);
    $error = "[$errNo]<br>";

    foreach ($backtrace as $trace) {
        if (isset($trace['file']) && isset($trace['line']))
            $error .= '<span class="select-on-click">' . $trace['file'] . ':' . $trace['line'] . '</span><br>';
    }
    $error .= "<span class=\"select-on-click\">$errFile:$errLine</span> <span class=\"red\">$errStr<span><br>";

    devlog($error);

    return true; // Skip internal PHP error handler
}

$oldErrorHandler = set_error_handler("myErrorHandler");




function geD() {
    $dump      = '';
    $backtrace = array_reverse(debug_backtrace());
    // array_shift($backtrace);
    // array_shift($backtrace);

    foreach ($backtrace as $trace) {
        $dump .= '<span class="select-on-click">' . ($trace['file'] ?? $trace['function'] ?? '??') . ':' . ($trace['line'] ?? $trace['args'][0] ?? '??') . '</span><br>';
    }
    foreach (func_get_args() as $arg) {
        ob_start();
        var_dump($arg);
        $dumpTemp = ob_get_clean();
        $dumpTemp = preg_replace('/=>\n\s+/m', ' => ', $dumpTemp);
        $dumpTemp = highlight_string('<?php ' . $dumpTemp, true);
        $dumpTemp = str_replace('&lt;?php&nbsp;', '', $dumpTemp);
        $dump     .= $dumpTemp . PHP_EOL;
    }
    devlog($dump);
}




function geErrorPage($code, $msg = '') {
    Director::errorPage($code, $msg);
}




// from https://api.drupal.org/api/drupal/namespace/Drupal%21Component%21Utility/8.8.x
function getUploadMaxSize(): int {
    static $maxSize = -1;
    if ($maxSize < 0) {

        $maxSize = sizeShorthandToInt(ini_get('post_max_size'));

        $uploadMax = sizeShorthandToInt(ini_get('upload_max_filesize'));
        if ($uploadMax > 0 && $uploadMax < $maxSize) {
            $maxSize = $uploadMax;
        }
    }

    return $maxSize;
}

// converts for example 1M => 1048576 or 1k => 1024
function sizeShorthandToInt(string $size): int {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);

    $size = preg_replace('/[^0-9\\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    } else {
        return round($size);
    }
}



// galaxiaChat

function exitArrayToJson($r) {
    header('Content-Type: application/json');
    exit(json_encode($r, JSON_PRETTY_PRINT));
}



