<?php
/* Copyright 2019 Ino Detelić

 - Licensed under the EUPL, Version 1.2 only (the "Licence");
 - You may not use this work except in compliance with the Licence.

 - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

 - Unless required by applicable law or agreed to in writing, software distributed
   under the Licence is distributed on an "AS IS" basis,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 - See the Licence for the specific language governing permissions and limitations under the Licence.
*/

namespace Galaxia;
use IntlDateFormatter;
use mysqli;
use Transliterator;


class Director {

    private static $app     = null;
    private static $editor  = null;
    private static $me      = null;
    private static $mysqli  = null;


    static $transliterator      = null;
    static $transliteratorLower = null;
    static $intlDateFormatters  = [];

    static $translations = [];
    static $ajax = false;
    static $nofollowHosts = ['facebook', 'google', 'instagram', 'twitter', 'linkedin', 'youtube']; // todo: refactor

    /**
     * @deprecated
     */
    static $pDefault = [
        'id'      => '', // current subpage or page id
        'type'    => 'default',
        'status'  => 1,
        'url'     => [],
        'slug'    => [],
        'title'   => [],
        'noindex' => false,
        'ogImage' => '',
    ];

    // dev and debug
    static $dev = false;
    static $debug = false;
    private static $timers      = [];
    private static $timerLevel  = 0;
    private static $timerMaxLen = 0;
    private static $timerMaxLev = 0;




    static function init(string $dir): App {
        // register_shutdown_function('\Galaxia\Director::onShutdown');

        if (getenv('GALAXIA_ENV') == 'development') self::$dev = true;

        if (self::$app)    self::errorPageAndExit(500, 'Director app initialization', __METHOD__ . ':' . __LINE__ . ' App was already initialized');
        if (!$dir)         self::errorPageAndExit(500, 'Director app initialization', __METHOD__ . ':' . __LINE__ . ' $dir is empty');
        if (!is_dir($dir)) self::errorPageAndExit(500, 'Director app initialization', __METHOD__ . ':' . __LINE__ . ' $dir is not a directory');
        if (file_exists($dir . '/config/const.php')) include $dir . '/config/const.php';
        if (!file_exists($dir . '/config/app.php')) self::errorPageAndExit(500, 'Director app initialization', __METHOD__ . ':' . __LINE__ . ' App was already initialized');

        header('Content-Type: text/html; charset=utf-8');
        header_remove("X-Powered-By");

        ini_set('display_errors', '0');
        libxml_use_internal_errors(true);

        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') == 'XMLHttpRequest') self::$ajax = true;


        $app = new App($dir);

        if ($app->cookieDebugVal && $app->cookieDebugKey && isset($_COOKIE)) {
            if (($_COOKIE[$app->cookieDebugKey] ?? null) === $app->cookieDebugVal) {
                self::debugEnable();
            }
        }

        self::$app = $app;
        self::timerStart('app total', $_SERVER['REQUEST_TIME_FLOAT']);
        return self::$app;
    }




    static function initCLI(string $dir): App {
        if (self::$app)    self::errorPageAndExit(500, 'Director app CLI initialization', __METHOD__ . ':' . __LINE__ . ' App was already initialized');
        if (!$dir)         self::errorPageAndExit(500, 'Director app CLI initialization', __METHOD__ . ':' . __LINE__ . ' $dir is empty');
        if (!is_dir($dir)) self::errorPageAndExit(500, 'Director app CLI initialization', __METHOD__ . ':' . __LINE__ . ' $dir is not a directory');
        if (file_exists($dir . '/config/const.php')) include $dir . '/config/const.php';
        if (!file_exists($dir . '/config/app.php')) self::errorPageAndExit(500, 'Director app CLI initialization', __METHOD__ . ':' . __LINE__ . ' App was already initialized');

        libxml_use_internal_errors(true);

        self::debugEnable();

        $app = new App($dir);

        self::$app = $app;
        self::timerStart('app total', $_SERVER['REQUEST_TIME_FLOAT']);
        return self::$app;
    }




    static function initEditor(string $dir): Editor {
        if (!self::$app)   self::errorPageAndExit(500, 'Director editor initialization', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        if (self::$editor) self::errorPageAndExit(500, 'Director editor initialization', __METHOD__ . ':' . __LINE__ . ' Editor was already initialized');
        if (!$dir)         self::errorPageAndExit(500, 'Director editor initialization', __METHOD__ . ':' . __LINE__ . ' $dir is empty');
        if (!is_dir($dir)) self::errorPageAndExit(500, 'Director editor initialization', __METHOD__ . ':' . __LINE__ . ' $dir is not a directory');

        $editor = new Editor($dir);
        self::$editor = $editor;
        return $editor;
    }




    static function initMe(): User {
        if (!self::$app) self::errorPageAndExit(500, 'Director user initialization', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        if (self::$me)   self::errorPageAndExit(500, 'Director user initialization', __METHOD__ . ':' . __LINE__ . ' User was already initialized');
        $me = new User('_geUser');
        self::$me = $me;
        return $me;
    }




    static function debugEnable() {
        self::$debug = true;
        ini_set('display_errors', '1');
        error_reporting(E_ALL);
    }




    static function getApp(): App {
        if (!self::$app) self::errorPageAndExit(500, 'Director app', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        return self::$app;
    }




    static function editor(): Editor {
        if (!self::$app)  db();  self::errorPageAndExit(500, 'Director editor', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        if (!self::$editor) self::errorPageAndExit(500, 'Director editor', __METHOD__ . ':' . __LINE__ . ' Editor was not initialized');
        return self::$editor;
    }




    static function getMe($userTable): User {
        if (!self::$app) self::errorPageAndExit(500, 'Director me', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        if (!self::$me)  self::errorPageAndExit(500, 'Director me', __METHOD__ . ':' . __LINE__ . ' User was not initialized');
        return self::$me;
    }




    static function getMysqli(): mysqli {
        if (!self::$app) self::errorPageAndExit(500, 'Director db', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        if (!self::$mysqli) {
            self::timerStart('DB Connection');

            self::$mysqli = new mysqli(self::$app->mysqlHost, self::$app->mysqlUser, self::$app->mysqlPass, self::$app->mysqlDb);
            if (self::$mysqli->connect_errno) {
                self::errorPageAndExit(500, 'Director db Connection Failed' . __METHOD__ . ':' . __LINE__ . ' ' . self::$mysqli->connect_errno);
            }
            if (self::$debug) mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            self::$mysqli->set_charset('utf8mb4');
            self::$mysqli->query('SET time_zone = ' . q(self::$app->timeZone) . ';');
            self::$mysqli->query('SET lc_time_names = ' . q(self::$app->locale['long']) . ';');

            self::timerStop('DB Connection');
        }
        return self::$mysqli;
    }




    static function getTransliteratorLower() {
        if (self::$transliteratorLower == null) {
            self::$transliteratorLower = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;');
        }
        return self::$transliteratorLower;
    }




    static function getTransliterator() {
        if (self::$transliterator == null) {
            self::$transliterator = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;');
        }
        return self::$transliterator;
    }




    static function getIntlDateFormatter($pattern, $lang) {
        if (!isset(self::$intlDateFormatters[$pattern][$lang])) {
            self::$intlDateFormatters[$pattern][$lang] = new IntlDateFormatter(
                $lang,                         // locale
                IntlDateFormatter::FULL,      // datetype
                IntlDateFormatter::NONE,      // timetype
                null,                          // timezone
                IntlDateFormatter::GREGORIAN, // calendar
                $pattern                       // pattern
            );
        }
        return self::$intlDateFormatters[$pattern][$lang];
    }




    static function loadTranslations() {
        self::timerStart('Translations');

        if (self::$editor && file_exists(self::$editor->dir . 'resource/stringTranslations.php'))
            self::$translations = array_merge(self::$translations, include(self::$editor->dir . 'resource/stringTranslations.php'));

        if (self::$app && file_exists(self::$app->dir . 'resource/stringTranslations.php'))
            self::$translations = array_merge(self::$translations, include(self::$app->dir . 'resource/stringTranslations.php'));

        self::timerStop('Translations');
    }




    // timing

    static function timerStart(string $timerName, $timeFloat = null) {
        if (!self::$debug) return;
        if (isset(self::$timers[$timerName])) {
            if (self::$timers[$timerName]['running']) return;
            self::$timers[$timerName]['lap'] = microtime(true);
            self::$timers[$timerName]['running'] = true;
        } else {
            self::$timerLevel++;
            self::$timers[$timerName] = [
                'start'   => $timeFloat ?? microtime(true),
                'end'     => 0,
                'level'   => self::$timerLevel,
                'running' => true,
                'total'   => 0,
                'lap'     => $timeFloat ?? 0,
                'count'   => 0,
            ];
            self::$timers[$timerName]['lap'] = self::$timers[$timerName]['start'];
            self::$timerMaxLen = max(self::$timerMaxLen, (self::$timerLevel * 2) + strlen($timerName));
            self::$timerMaxLev = max(self::$timerMaxLev, self::$timerLevel);
        }
    }

    static function timerStop(string $timerName) {
        if (!self::$debug) return;
        if (!isset(self::$timers[$timerName])) return;
        if (!self::$timers[$timerName]['running']) return;

        if (self::$timerLevel > 0) self::$timerLevel--;
        self::$timers[$timerName]['end'] = microtime(true);
        self::$timers[$timerName]['total'] += self::$timers[$timerName]['end'] - self::$timers[$timerName]['lap'];
        self::$timers[$timerName]['running'] = false;
        self::$timers[$timerName]['lap'] = 0;
        self::$timers[$timerName]['count']++;
    }

    static function timerPrint(bool $comments = false, bool $memory = false) {
        if (!self::$debug) return;
        $timeEnd = microtime(true);
        self::$timers['app total']['end'] = microtime(true);
        self::$timers['app total']['total'] += self::$timers['app total']['end'] - self::$timers['app total']['lap'];
        self::$timers['app total']['running'] = false;
        self::$timers['app total']['lap'] = 0;
        self::$timers['app total']['count']++;

        $r = '';
        $prefix = '';
        $postfix = '' . PHP_EOL;
        if ($comments) {
            $prefix = '<!-- ';
            $postfix = ' -->' . PHP_EOL;
        }

        $timeTotal = self::$timers['app total']['total'];
        $levelTotals = [$timeTotal];
        $pad = '.';
        $r .= $prefix .
            '..... start' .
            ' .. #' .
            ' ..... time' .
            ' .... %' .
            ' ..' . str_repeat(' .%', self::$timerMaxLev - 1) .
            ' .' . $postfix;

        foreach (self::$timers as $timerName => $time) {

            $percentOfParent = '';
            $levelTotals[$time['level']] = $time['total'];

            if ($time['level'] > 0) {
                $divisor = $levelTotals[$time['level'] - 1];
                if ($divisor == 0) $divisor = 1;
                $percentOfParent = (($time['total'] * 100) / $divisor);
                $percentOfParent = number_format($percentOfParent, 0, '.', ' ');
            }

            if ($percentOfParent > 99) $percentOfParent = 99;

            $r .= $prefix . str_pad(' ' . number_format(($time['start'] - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 3, '.', ' '), 11, $pad, STR_PAD_LEFT) . ' ';
            $r .= str_pad(' ' . $time['count'], 4, $pad, STR_PAD_LEFT) . ' ';

            if ($time['running']) {
                $r .= str_pad(' ' . number_format(($timeEnd - $time['start']) * 1000, 2, '.', ' '), 10, $pad, STR_PAD_LEFT) . ' ';
            } else {
                $r .= str_pad(' ' . number_format($time['total'] * 1000, 2, '.', ' '), 10, $pad, STR_PAD_LEFT) . ' ';
            }

            $r .= str_pad(number_format((($time['total'] * 100) / $timeTotal), 2, '.', ' '), 6, $pad, STR_PAD_LEFT) . ' ';

            if ($time['level'] > 1) {
                $r .= str_repeat('.. ', $time['level'] - 1) . str_pad($percentOfParent, 2, '.', STR_PAD_LEFT) . ' ';
            } else {
                $r .= '.. ';
            }

            $r .= str_repeat('.. ', self::$timerMaxLev - $time['level']);

            if ($time['running']) {
                $r .= str_pad(str_repeat($pad . $pad, $time['level'] - 1) . ' ' . $timerName, self::$timerMaxLen + 1, ' ', STR_PAD_RIGHT) . $postfix;
            } else {
                $r .= str_pad(str_repeat($pad . $pad, $time['level'] - 1) . (($time['level'] > 0) ? ' ' : '') . $timerName, self::$timerMaxLen + 1, ' ', STR_PAD_RIGHT) . $postfix;
            }

        }

        if ($memory) {
            $r .= $prefix . 'script peak ...' . str_pad(' ' . number_format(memory_get_peak_usage()), 12, '.', STR_PAD_LEFT) . ' bytes' . $postfix;
            $r .= $prefix . 'system at end .' . str_pad(' ' . number_format(memory_get_usage(false)), 12, '.', STR_PAD_LEFT) . ' bytes' . $postfix;
            $r .= $prefix . 'system peak ...' . str_pad(' ' . number_format(memory_get_peak_usage(true)), 12, '.', STR_PAD_LEFT) . ' bytes' . $postfix;
        }
        echo $r;
    }




    // shutdown functions

    static function onShutdown() {
        Director::timerPrint(true, true);
    }

    static function onShutdownCLI() {
        if (haserror()) {
            echo '🍎 errors: ' . PHP_EOL;
            foreach (errors() as $key => $msgs) {
                echo '    ' . escapeshellcmd($key) . PHP_EOL;
                foreach ($msgs as $msg) {
                    echo '        ' . escapeshellcmd($msg) . PHP_EOL;
                }
            }
        }
        if (haswarning()) {
            echo '🍋 warnings: ' . PHP_EOL;
            foreach (warnings() as $key => $msgs) {
                echo '    ' . escapeshellcmd($key) . PHP_EOL;
                foreach ($msgs as $msg) {
                    echo '        ' . escapeshellcmd($msg) . PHP_EOL;
                }
            }
        }
        if (hasinfo()) {
            echo '🍐 infos: ' . PHP_EOL;
            foreach (infos() as $key => $msgs) {
                echo '    ' . escapeshellcmd($key) . PHP_EOL;
                foreach ($msgs as $msg) {
                    echo '        ' . escapeshellcmd($msg) . PHP_EOL;
                }
            }
        }
        if (hasdevlog()) {
            echo '🥔 devlogs: ' . PHP_EOL;
            foreach (devlogs() as $key => $msgs) {
                echo '    ' . escapeshellcmd($key) . PHP_EOL;
                foreach ($msgs as $msg) {
                    echo '        ' . escapeshellcmd($msg) . PHP_EOL;
                }
            }
        }
        Director::timerPrint();
    }




    // general error page

    static function errorPageAndExit($errorCode, $msg = '', $debugText = '') {

        $errors = [
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
        ];

        if (!in_array($errorCode, [403, 404, 500])) $errorCode = 500;
        http_response_code($errorCode);

        $prefix = '<!-- ';
        $suffix = ' -->' . PHP_EOL;
        if (php_sapi_name() == 'cli') {
            echo 'Error: ' . h($errorCode) . ' - ' . h($errors[$errorCode]) . ' - ' . $debugText;
            exit();
        } else {
            $errorFile = '';
            if (self::$app) {
                $errorFilesToCheck = [];
                foreach (self::$app->langs as $lang) {
                    $errorFilesToCheck[] = self::$app->dir . 'public/error/' . $errorCode . '-' . $lang . '.html';
                }
                $errorFilesToCheck[] = self::$app->dir . 'public/' . $errorCode . '.html';

                foreach ($errorFilesToCheck as $errorFileToCheck) {
                    if (file_exists($errorFileToCheck)) {
                        $errorFile = $errorFileToCheck;
                        break;
                    }
                }
            }
            if ($errorFile) {
                include $errorFile;
            } else {
                $title = 'Error: ' . $errorCode . ' - ' . $errors[$errorCode];
                echo '<!doctype html><meta charset=utf-8><title>' . $title . '</title><body style="font-family: monospace;"><p style="font-size: 1.3em; margin-top: 4em; text-align: center;">' . $title . '</p>' . PHP_EOL;
            }
        }

        if ($msg) echo $prefix . ' Error: ' . $msg . $suffix;
        if (self::$debug) {
            if ($debugText) echo $prefix . ' Error: ' . $debugText . $suffix;

            $backtrace = debug_backtrace();
            foreach ($backtrace as $trace)
                if ($trace['file'] ?? '')
                    echo $prefix . $trace['file'] . ':' . $trace['line'] . $suffix;

            echo $prefix . ' ' . $debugText . $suffix;
        }
        exit();

    }

}
