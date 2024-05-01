<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


class Flash {

    static function msgBoxes($type, $arrayIndex = false) {
        $key    = $type . 's';
        $domain = $type . 'Box';
        if ($arrayIndex !== false) return $_SESSION[$key][$domain][$arrayIndex] ?? [];

        return $_SESSION[$key][$domain] ?? [];
    }




    static function error($msg, $domain = 'errorBox', $arrayIndex = false): void {
        if ($arrayIndex !== false) {
            $_SESSION['errors'][$domain][$arrayIndex][] = $msg;
        } else {
            $_SESSION['errors'][$domain][] = $msg;
        }
    }

    static function hasError($domain = null, $arrayIndex = false): bool {
        if (isset($domain)) {
            if ($arrayIndex !== false) return (isset($_SESSION['errors'][$domain][$arrayIndex]));

            return (isset($_SESSION['errors'][$domain]));
        } else {
            return (isset($_SESSION['errors']));
        }
    }

    static function errors($domain = null, $arrayIndex = false) {
        if (isset($domain)) {
            if ($arrayIndex !== false) return $_SESSION['errors'][$domain][$arrayIndex] ?? [];

            return $_SESSION['errors'][$domain] ?? [];
        } else {
            return $_SESSION['errors'] ?? [];
        }
    }




    static function warning($msg, $domain = 'warningBox', $arrayIndex = false): void {
        if ($arrayIndex !== false) {
            $_SESSION['warnings'][$domain][$arrayIndex][] = $msg;
        } else {
            $_SESSION['warnings'][$domain][] = $msg;
        }
    }

    static function hasWarning($domain = null, $arrayIndex = false): bool {
        if (isset($domain)) {
            if ($arrayIndex !== false) return (isset($_SESSION['warnings'][$domain][$arrayIndex]));

            return (isset($_SESSION['warnings'][$domain]));
        } else {
            return (isset($_SESSION['warnings']));
        }
    }

    static function warnings($domain = null, $arrayIndex = false) {
        if (isset($domain)) {
            if ($arrayIndex !== false) return $_SESSION['warnings'][$domain][$arrayIndex] ?? [];

            return $_SESSION['warnings'][$domain] ?? [];
        } else {
            return $_SESSION['warnings'] ?? [];
        }
    }




    static function info($msg, $domain = 'infoBox', $arrayIndex = false): void {
        if ($arrayIndex !== false) {
            $_SESSION['infos'][$domain][$arrayIndex][] = $msg;
        } else {
            $_SESSION['infos'][$domain][] = $msg;
        }
    }

    static function hasInfo($domain = null, $arrayIndex = false): bool {
        if (isset($domain)) {
            if ($arrayIndex !== false) return (isset($_SESSION['infos'][$domain][$arrayIndex]));

            return (isset($_SESSION['infos'][$domain]));
        } else {
            return (isset($_SESSION['infos']));
        }
    }

    static function infos($domain = null, $arrayIndex = false) {
        if (isset($domain)) {
            if ($arrayIndex !== false) return $_SESSION['infos'][$domain][$arrayIndex] ?? [];

            return $_SESSION['infos'][$domain] ?? [];
        } else {
            return $_SESSION['infos'] ?? [];
        }
    }




    static function devlog($msg, $domain = 'devlogBox', $arrayIndex = false): void {
        if ($arrayIndex !== false) {
            $_SESSION['devlogs'][$domain][$arrayIndex][] = $msg;
        } else {
            $_SESSION['devlogs'][$domain][] = $msg;
        }
    }

    static function hasDevlog($domain = null, $arrayIndex = false): bool {
        if (isset($domain)) {
            if ($arrayIndex !== false) return (isset($_SESSION['devlogs'][$domain][$arrayIndex]));

            return (isset($_SESSION['devlogs'][$domain]));
        } else {
            return (isset($_SESSION['devlogs']));
        }
    }

    static function devlogs($domain = null, $arrayIndex = false) {
        if (isset($domain)) {
            if ($arrayIndex !== false) return $_SESSION['devlogs'][$domain][$arrayIndex] ?? [];

            return $_SESSION['devlogs'][$domain] ?? [];
        } else {
            return $_SESSION['devlogs'] ?? [];
        }
    }




    static function cleanMessages(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) return;
        unset($_SESSION['errors']);
        unset($_SESSION['infos']);
        unset($_SESSION['warnings']);
        unset($_SESSION['devlogs']);
    }




    static function printCli(): void {
        if (Flash::hasError()) {
            echo '🔴 errors: ' . PHP_EOL;
            foreach (Flash::errors() as $key => $msgs) {
                d($key, $msgs);
            }
        }
        if (Flash::hasWarning()) {
            echo '🟡 warnings: ' . PHP_EOL;
            foreach (Flash::warnings() as $key => $msgs) {
                d($key, $msgs);
            }
        }
        if (Flash::hasInfo()) {
            echo '🟢 infos: ' . PHP_EOL;
            foreach (Flash::infos() as $key => $msgs) {
                d($key, $msgs);
            }
        }
        if (Flash::hasDevlog()) {
            echo '🟣️ devlogs: ' . PHP_EOL;
            foreach (Flash::devlogs() as $key => $msgs) {
                d($key, $msgs);
            }
        }
    }

}
