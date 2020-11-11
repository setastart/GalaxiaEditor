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


use mysqli;
use Throwable;


class Director {

    private static $app    = null;
    private static $editor = null;
    private static $me     = null;
    private static $mysqli = null;

    /**
     * @deprecated
     */
    static $ajax = false;

    static $nofollowHosts = ['facebook', 'google', 'instagram', 'twitter', 'linkedin', 'youtube']; // todo: refactor

    private static $timers      = [];
    private static $timerLevel  = 0;
    private static $timerMaxLen = 0;
    private static $timerMaxLev = 0;


    static function init(string $dir): App {
        self::initEnv();

        header('Content-Type: text/html; charset=utf-8');
        header_remove("X-Powered-By");

        if (self::$app) self::errorPage(500, 'Director app initialization', __METHOD__ . ':' . __LINE__ . ' App was already initialized');
        if (!$dir) self::errorPage(500, 'Director app initialization', __METHOD__ . ':' . __LINE__ . ' $dir is empty');
        if (!is_dir($dir)) self::errorPage(500, 'Director app initialization', __METHOD__ . ':' . __LINE__ . ' $dir is not a directory');
        if (!file_exists($dir . '/config/app.php')) self::errorPage(500, 'Director app initialization', __METHOD__ . ':' . __LINE__ . ' App was already initialized');

        libxml_use_internal_errors(true);

        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') == 'XMLHttpRequest') self::$ajax = true;

        $app = new App($dir);

        include $app->dir . 'config/app.php';
        if (file_exists($app->dir . 'config/app.private.php')) include $app->dir . 'config/app.private.php';

        self::$app = $app;
        self::timerStart('Total', $_SERVER['REQUEST_TIME_FLOAT']);

        return self::$app;
    }




    static function initCLI(string $dir): App {
        self::initEnv();

        if (self::$app) self::errorPage(500, 'Director app CLI initialization', __METHOD__ . ':' . __LINE__ . ' App was already initialized');
        if (!$dir || !is_dir($dir)) self::errorPage(500, 'Director app CLI initialization', __METHOD__ . ':' . __LINE__ . ' $dir is not a directory');
        if (!file_exists($dir . '/config/app.php')) self::errorPage(500, 'Director app CLI initialization', __METHOD__ . ':' . __LINE__ . ' App config not found');

        libxml_use_internal_errors(true);

        $app = new App($dir);

        include $app->dir . 'config/app.php';
        if (file_exists($app->dir . 'config/app.private.php')) include $app->dir . 'config/app.private.php';

        self::$app = $app;
        self::timerStart('Total', $_SERVER['REQUEST_TIME_FLOAT']);

        return self::$app;
    }




    private static function initEnv() {
        error_reporting(E_ALL);

        set_exception_handler(function(Throwable $e) {
            self::errorPage($e->getCode(), $e->getMessage());
        });

        set_error_handler(function($code, $msg, $file = '', $line = 0) {
            self::errorPage($code, $msg, $file . ':' . $line);

            return true;
        });

        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error !== null) {
                self::errorPage(500, $error['message'], $error['type'] . ' - ' . $error['file'] . ':' . $error['line']);
            }
            exit();
        });

        if (self::isDevEnv() || self::isCli()) {
            ini_set('display_errors', '1');
        } else {
            ini_set('display_errors', '0');
        }
    }




    static function initEditor(string $dir): Editor {
        if (!self::$app) self::errorPage(500, 'Director editor initialization', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        if (self::$editor) self::errorPage(500, 'Director editor initialization', __METHOD__ . ':' . __LINE__ . ' Editor was already initialized');
        if (!$dir || !is_dir($dir)) self::errorPage(500, 'Director editor initialization', __METHOD__ . ':' . __LINE__ . ' $dir is not a directory');

        self::$editor = new Editor($dir);

        return self::$editor;
    }




    static function initMe(string $userTable = '_geUser'): User {
        if (!self::$app) self::errorPage(500, 'Director user initialization', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        if (self::$me) self::errorPage(500, 'Director user initialization', __METHOD__ . ':' . __LINE__ . ' User was already initialized');
        self::$me = new User($userTable);

        return self::$me;
    }




    static function isDevEnv(): bool {
        return getenv('GALAXIA_ENV') === 'development';
    }




    static function isDev(): bool {
        if (!self::$me) return false;
        if (!self::$me->loggedIn) return false;

        return self::$me->hasPerm('dev');
    }


    static function isDevDebug(): bool {
        if (!self::isDev()) return false;
        if (!self::$app || !self::$app->cookieDebugVal || !self::$app->cookieDebugKey || !isset($_COOKIE)) return false;

        return ($_COOKIE[self::$app->cookieDebugKey] ?? null) === self::$app->cookieDebugVal;
    }



    static function isCli(): bool {
        return php_sapi_name() == 'cli';
    }



    static function insideEditor(): bool {
        if (!self::$editor) return false;

        return ($_SERVER['DOCUMENT_ROOT'] ?? null) === self::$editor->dir . 'public';
    }




    static function getApp(): App {
        if (!self::$app) self::errorPage(500, 'Director error', __METHOD__ . ':' . __LINE__ . ' App was not initialized');

        return self::$app;
    }




    static function getEditor(): Editor {
        if (!self::$app) self::errorPage(500, 'Director error', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        if (!self::$editor) self::errorPage(500, 'Director error', __METHOD__ . ':' . __LINE__ . ' Editor was not initialized');

        return self::$editor;
    }




    static function getMe(): User {
        if (!self::$app) self::errorPage(500, 'Director error', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        if (!self::$me) self::errorPage(500, 'Director error', __METHOD__ . ':' . __LINE__ . ' User was not initialized');

        return self::$me;
    }




    static function getMysqli(): mysqli {
        if (!self::$app) self::errorPage(500, 'Director db', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        if (!self::$mysqli) {
            self::timerStart('DB Connection');

            self::$mysqli = new mysqli(self::$app->mysqlHost, self::$app->mysqlUser, self::$app->mysqlPass, self::$app->mysqlDb);
            if (self::$mysqli->connect_errno) {
                self::errorPage(500, 'Director db Connection Failed' . __METHOD__ . ':' . __LINE__ . ' ' . self::$mysqli->connect_errno);
            }
            if (self::isDev()) mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            self::$mysqli->set_charset('utf8mb4');
            self::$mysqli->query('SET time_zone = ' . Text::q(self::$app->timeZone) . ';');
            self::$mysqli->query('SET lc_time_names = ' . Text::q(self::$app->locale['long']) . ';');

            self::timerStop('DB Connection');
        }

        return self::$mysqli;
    }




    static function loadTranslations() {
        self::timerStart('Translations');

        if (self::$editor && file_exists(self::$editor->dir . 'resource/stringTranslations.php'))
            Text::$translations = array_merge(Text::$translations, include(self::$editor->dir . 'resource/stringTranslations.php'));

        if (self::$app && file_exists(self::$app->dir . 'resource/stringTranslations.php'))
            Text::$translations = array_merge(Text::$translations, include(self::$app->dir . 'resource/stringTranslations.php'));

        self::timerStop('Translations');
    }




    // timing

    static function timerStart(string $timerLabel, $timeFloat = null) {
        // if (self::$me && !self::isDev()) return;

        if (isset(self::$timers[$timerLabel])) {
            if (self::$timers[$timerLabel]['running']) return;
            self::$timers[$timerLabel]['lap']     = microtime(true);
            self::$timers[$timerLabel]['running'] = true;
            self::$timers[$timerLabel]['mem']     = memory_get_usage(false);
        } else {
            self::$timerLevel++;
            self::$timers[$timerLabel]        = [
                'start'   => $timeFloat ?? microtime(true),
                'end'     => 0,
                'level'   => self::$timerLevel,
                'running' => true,
                'total'   => 0,
                'lap'     => $timeFloat ?? 0,
                'count'   => 0,
                'mem'     => memory_get_usage(false),
            ];
            self::$timers[$timerLabel]['lap'] = self::$timers[$timerLabel]['start'];
            self::$timerMaxLen                = max(self::$timerMaxLen, (self::$timerLevel * 2) + strlen($timerLabel));
            self::$timerMaxLev                = max(self::$timerMaxLev, self::$timerLevel);
        }
    }


    static function timerStop(string $timerLabel) {
        // if (self::$me && !self::isDev()) return;

        if (!isset(self::$timers[$timerLabel])) return;
        // if (!self::$timers[$timerLabel]['running']) return;

        if (self::$timerLevel > 0) self::$timerLevel--;
        self::$timers[$timerLabel]['end']     = microtime(true);
        self::$timers[$timerLabel]['total']   += self::$timers[$timerLabel]['end'] - self::$timers[$timerLabel]['lap'];
        self::$timers[$timerLabel]['running'] = false;
        self::$timers[$timerLabel]['lap']     = 0;
        self::$timers[$timerLabel]['count']++;
        self::$timers[$timerLabel]['mem'] = memory_get_usage(false) - self::$timers[$timerLabel]['mem'];
    }


    static function timerPrint(bool $comments = false, bool $memory = false) {
        if (!self::isDevEnv() && !self::isDev()) return;

        $timeEnd = microtime(true);

        self::$timers['Total']['end']     = $timeEnd;
        self::$timers['Total']['total']   += self::$timers['Total']['end'] - self::$timers['Total']['lap'];
        self::$timers['Total']['running'] = false;
        self::$timers['Total']['lap']     = 0;
        self::$timers['Total']['count']++;
        self::$timers['Total']['mem'] = memory_get_usage(false) - self::$timers['Total']['mem'];

        $r       = '';
        $prefix  = '';
        $postfix = '' . PHP_EOL;

        if ($comments) {
            $prefix  = '<!-- ';
            $postfix = ' -->' . PHP_EOL;
        }

        $timeTotal   = self::$timers['Total']['total'];
        $levelTotals = [$timeTotal];
        $pad         = '`';

        $cols   = [];
        $colLen = [];

        foreach (self::$timers as $timerLabel => $time) {
            $percentOfParent             = '';
            $levelTotals[$time['level']] = $time['total'];

            if ($time['level'] > 0) {
                $divisor = $levelTotals[$time['level'] - 1];
                if ($divisor == 0) $divisor = 1;
                $percentOfParent = (($time['total'] * 100) / $divisor);
                $percentOfParent = number_format($percentOfParent, 0, '.', ',');
            }

            // if ($percentOfParent > 99) $percentOfParent = 99;

            $cols[$timerLabel]['start'] = number_format(($time['start'] - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 3, '.', ',');
            $cols[$timerLabel]['#']     = $time['count'];
            if ($time['running']) {
                $cols[$timerLabel]['time'] = number_format(($timeEnd - $time['start']) * 1000, 2, '.', ',');
            } else {
                $cols[$timerLabel]['time'] = number_format($time['total'] * 1000, 2, '.', ',');
            }

            $cols[$timerLabel]['mem'] = Text::filesize($time['mem'], 2, '.');
            $cols[$timerLabel]['%']   = number_format((($time['total'] * 100) / $timeTotal), 2, '.', ',');

            $cols[$timerLabel][$time['level']] = $percentOfParent;
            $cols[$timerLabel]['label']        = str_repeat($pad . $pad, max(0, $time['level'] - 2)) . $timerLabel;

            $colLen['start']        = max(strlen('start'), $colLen['start'] ?? 0, strlen($cols[$timerLabel]['start'] ?? ''));
            $colLen['#']            = max(strlen('#'), $colLen['#'] ?? 0, strlen($cols[$timerLabel]['#'] ?? ''));
            $colLen['mem']          = max(strlen('mem'), $colLen['mem'] ?? 0, strlen($cols[$timerLabel]['mem'] ?? ''));
            $colLen['time']         = max(strlen('time'), $colLen['time'] ?? 0, strlen($cols[$timerLabel]['time'] ?? ''));
            $colLen['%']            = max(strlen('%'), $colLen['%'] ?? 0, strlen($cols[$timerLabel]['%'] ?? ''));
            $colLen[$time['level']] = max($colLen[$time['level']] ?? 0, strlen($cols[$timerLabel][$time['level']] ?? ''));
        }
        foreach ($cols as $timerLabel => $time) {
            $colLen['label'] = max($colLen['label'] ?? 0, strlen($cols[$timerLabel]['label'] ?? ''));
        }

        $r .= $prefix;
        foreach ($colLen as $col => $len) {
            if ($col == 'label') {
                $r .= str_pad($col ?? '', $len, ' ', STR_PAD_RIGHT) . ' ';
            } else {
                if (is_int($col)) {
                    if ($col < 2) continue;
                    $col -= 1;
                }
                $r .= str_pad($col ?? '', $len, '.', STR_PAD_LEFT) . ' ';
            }
        }
        $r .= $postfix;


        foreach ($cols as $timerLabel => $val) {
            $r .= $prefix;
            foreach ($colLen as $col => $len) {
                if ($col == 'label') {
                    $r .= str_pad($val[$col] ?? '', $len, ' ', STR_PAD_RIGHT) . ' ';
                } else {
                    if (is_int($col) && $col < 2) continue;

                    $r .= str_pad($val[$col] ?? '', $len, $pad, STR_PAD_LEFT) . ' ';
                }
                // $len   += 1;
            }
            $r .= $postfix;
        }

        $memEnd      = ' ' . Text::filesize(memory_get_usage(false), 2, '.');
        $memPeak     = ' ' . Text::filesize(memory_get_peak_usage(false), 2, '.');
        $memEndReal  = ' ' . Text::filesize(memory_get_usage(true), 2, '.');
        $memPeakReal = ' ' . Text::filesize(memory_get_peak_usage(true), 2, '.');
        $memLenMax   = ' ' . max(strlen($memEnd), strlen($memPeak), strlen($memEndReal), strlen($memPeakReal));

        if ($memory) {
            $r .= $prefix . 'mem at end .' . str_pad($memPeak, $memLenMax, '.', STR_PAD_LEFT) . $postfix;
            $r .= $prefix . 'mem peak ...' . str_pad($memPeak, $memLenMax, '.', STR_PAD_LEFT) . $postfix;
            $r .= $prefix . 'mem real at end .' . str_pad($memEndReal, $memLenMax, '.', STR_PAD_LEFT) . $postfix;
            $r .= $prefix . 'mem real peak ...' . str_pad($memPeakReal, $memLenMax, '.', STR_PAD_LEFT) . ' / ' . ini_get('memory_limit') . $postfix;
        }

        echo $r;
    }


    static function timerReset() {
        self::$timers      = [];
        self::$timerLevel  = 0;
        self::$timerMaxLen = 0;
        self::$timerMaxLev = 0;
        self::timerStart('Total', microtime(true));
    }




    // app + editor error page

    static function errorPage(int $code, string $msg = '', string $debugText = '') {
        $codeOriginal = $code;
        $errors       = [
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
        ];

        if (!in_array($code, [403, 404, 500])) $code = 500;
        http_response_code($code);

        $backtrace = array_reverse(debug_backtrace());

        if (self::isCli()) {
            d('Error: ' . Text::h($codeOriginal) . ' - ' . $msg);
            if (self::isDev()) {
                d($debugText);
            }
            db();
            exit();
        }

        if (self::insideEditor()) {
            if (self::$me && self::$me->loggedIn) {
                $errorCode = $code;
                $error     = $errors[$code] . '<br><br>';
                if (self::isDev()) {
                    $error .= 'Original error code: ' . $codeOriginal . '<br>';
                    $error .= nl2br($msg) . '<br><br>';
                    $error .= nl2br($debugText) . '<br>';
                }

                include self::$editor->dirLayout . 'layout-error.phtml';
                exit();
            }
        }

        $errorFile = '';
        if (self::$app) {
            $errorFilesToCheck = [];
            foreach (self::$app->langs as $lang) {
                $errorFilesToCheck[] = self::$app->dir . 'public/error/' . $code . '-' . $lang . '.html';
            }
            $errorFilesToCheck[] = self::$app->dir . 'public/' . $code . '.html';

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
            $title = 'Error: ' . $code . ' - ' . $errors[$code];
            echo '<!doctype html><meta charset=utf-8><title>' . $title . '</title><body style="font-family: monospace;"><p style="font-size: 1.3em; margin-top: 4em; text-align: center;">' . $title . '</p>' . PHP_EOL;
        }


        if (self::isDevEnv()) {
            d($msg);
            d($debugText);
            db();
        }

        exit();
    }


    static function redirect($location = '', int $code = 303, bool $addServerQuery = false) {
        $location = trim($location);
        if (headers_sent()) {
            echo 'headers already sent. redirect: <a href="' . Text::h($location) . '">' . Text::h($location) . '</a>' . PHP_EOL;
            exit();
        }
        $location = trim(Text::h($location), '/');
        if ($addServerQuery && $_SERVER['QUERY_STRING'] ?? '') $location .= '?' . $_SERVER['QUERY_STRING'];
        header('Location: /' . $location, true, $code);
        exit();
    }

}