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




// session messages

function msgBoxes($type, $arrayIndex = false) {
    $key = $type . 's';
    $domain = $type . 'Box';
    if ($arrayIndex !== false) return $_SESSION[$key][$domain][$arrayIndex] ?? [];
    return $_SESSION[$key][$domain] ?? [];
}




function error($msg, $domain = 'errorBox', $arrayIndex = false) {
    if ($arrayIndex !== false) {
        $_SESSION['errors'][$domain][$arrayIndex][] = $msg;
    } else {
        $_SESSION['errors'][$domain][] = $msg;
    }
}
function hasError($domain = null, $arrayIndex = false) {
    if (isset($domain)) {
        if ($arrayIndex !== false) return (isset($_SESSION['errors'][$domain][$arrayIndex]));
        return (isset($_SESSION['errors'][$domain]));
    } else {
        return (isset($_SESSION['errors']));
    }
}
function errors($domain = null, $arrayIndex = false) {
    if (isset($domain)) {
        if ($arrayIndex !== false) return $_SESSION['errors'][$domain][$arrayIndex] ?? [];
        return $_SESSION['errors'][$domain] ?? [];
    } else {
        return $_SESSION['errors'] ?? [];
    }
}




function warning($msg, $domain = 'warningBox', $arrayIndex = false) {
    if ($arrayIndex !== false) {
        $_SESSION['warnings'][$domain][$arrayIndex][] = $msg;
    } else {
        $_SESSION['warnings'][$domain][] = $msg;
    }
}
function hasWarning($domain = null, $arrayIndex = false) {
    if (isset($domain)) {
        if ($arrayIndex !== false) return (isset($_SESSION['warnings'][$domain][$arrayIndex]));
        return (isset($_SESSION['warnings'][$domain]));
    } else {
        return (isset($_SESSION['warnings']));
    }
}
function warnings($domain = null, $arrayIndex = false) {
    if (isset($domain)) {
        if ($arrayIndex !== false) return $_SESSION['warnings'][$domain][$arrayIndex] ?? [];
        return $_SESSION['warnings'][$domain] ?? [];
    } else {
        return $_SESSION['warnings'] ?? [];
    }
}




function info($msg, $domain = 'infoBox', $arrayIndex = false) {
    if ($arrayIndex !== false) {
        $_SESSION['infos'][$domain][$arrayIndex][] = $msg;
    } else {
        $_SESSION['infos'][$domain][] = $msg;
    }
}
function hasInfo($domain = null, $arrayIndex = false) {
    if (isset($domain)) {
        if ($arrayIndex !== false) return (isset($_SESSION['infos'][$domain][$arrayIndex]));
        return (isset($_SESSION['infos'][$domain]));
    } else {
        return (isset($_SESSION['infos']));
    }
}
function infos($domain = null, $arrayIndex = false) {
    if (isset($domain)) {
        if ($arrayIndex !== false) return $_SESSION['infos'][$domain][$arrayIndex] ?? [];
        return $_SESSION['infos'][$domain] ?? [];
    } else {
        return $_SESSION['infos'] ?? [];
    }
}




function devlog($msg, $domain = 'devlogBox', $arrayIndex = false) {
    if ($arrayIndex !== false) {
        $_SESSION['devlogs'][$domain][$arrayIndex][] = $msg;
    } else {
        $_SESSION['devlogs'][$domain][] = $msg;
    }
}
function hasDevlog($domain = null, $arrayIndex = false) {
    if (isset($domain)) {
        if ($arrayIndex !== false) return (isset($_SESSION['devlogs'][$domain][$arrayIndex]));
        return (isset($_SESSION['devlogs'][$domain]));
    } else {
        return (isset($_SESSION['devlogs']));
    }
}
function devlogs($domain = null, $arrayIndex = false) {
    if (isset($domain)) {
        if ($arrayIndex !== false) return $_SESSION['devlogs'][$domain][$arrayIndex] ?? [];
        return $_SESSION['devlogs'][$domain] ?? [];
    } else {
        return $_SESSION['devlogs'] ?? [];
    }
}




function cleanMessages() {
    if (session_status() !== PHP_SESSION_ACTIVE) return;
    unset($_SESSION['errors']);
    unset($_SESSION['infos']);
    unset($_SESSION['warnings']);
    unset($_SESSION['devlogs']);
}

