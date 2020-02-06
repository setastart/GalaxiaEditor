<?php

use Galaxia\{Director};



function prepareInput($input, $dirImage, $extras) {
    if ($input['type'] == 'select' && isset($input['geExtraOptions'])) {
        foreach ($input['geExtraOptions'] as $key => $val) {
            foreach ($val as $colKey => $colVals) {
                if (!isset($extras[$key])) continue;
                if (is_string($colVals)) $colVals = [$colVals];
                foreach ($extras[$key] as $option) {
                    $optionColVal = [];
                    foreach ($colVals as $colVal) {
                        $optionColVal[] = $option[$colVal];
                    }
                    $input['options'][$option[$colKey]] = ['label' => implode(' / ', $optionColVal)];
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
                $today = $todayDt->format('Y-m-d');
                $after = $afterDt->format('Y-m-d');
                $input['value'] = substr($today, 0, strspn($today ^ $after, "\0"));
                break;

            default:
                break;
        }
    }

    if (empty($input['label'])) $input['label'] = '';

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
        'uniqueId' => $uniqueId,
        'action' => $action,
        'tabName' => $tabName,
        'tabId' => $tabId,
        'fieldKey' => $fieldKey,
        'inputKey' => $inputKey,
        'content' => $content,
    ];
    $values = array_values($changes);
    $query = queryInsert(['_geHistory' => ['_geUserId', 'uniqueId', 'action', 'tabName', 'tabId', 'fieldKey', 'inputKey', 'content']], $changes);
    try {
        $db = Director::getMysqli();
        $stmt = $db->prepare($query);
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
    $dump = '';
    $backtrace = array_reverse(debug_backtrace());
    // array_shift($backtrace);
    // array_shift($backtrace);

    foreach ($backtrace as $trace) {
        $dump .= '<span class="select-on-click">' . $trace['file'] . ':' . $trace['line'] . '</span><br>';
    }
    foreach(func_get_args() as $arg) {
        ob_start();
        var_dump($arg);
        $dumpTemp = ob_get_clean();
        $dumpTemp = preg_replace('/=>\n\s+/m', ' => ', $dumpTemp);
        $dumpTemp = highlight_string('<?php ' . $dumpTemp, true);
        $dumpTemp = str_replace('&lt;?php&nbsp;', '', $dumpTemp);
        $dump .= $dumpTemp . PHP_EOL;
    }
    devlog($dump);
}




function geErrorPage($errorCode, $error = '') {
    global $editor;
    if (!in_array($errorCode, [403, 404, 500])) $errorCode = 500;
    http_response_code($errorCode);
    include $editor->dirLayout . 'layout-error.phtml';
    exit();
}




// galaxiaChat

function exitArrayToJson($r) {
    header('Content-Type: application/json');
    exit(json_encode($r, JSON_PRETTY_PRINT));
}

