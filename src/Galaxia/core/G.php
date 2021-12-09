<?php
/* Copyright 2017-2021 Ino Detelić & Zaloa G. Ramos

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


class G {

    public static Request $req;
    public static App     $app;
    public static Editor  $editor;
    public static User    $me;

    private static mysqli $mysqli;

    private static array $timers      = [];
    private static int   $timerLevel  = 0;
    private static int   $timerMaxLen = 0;
    private static int   $timerMaxLev = 0;


    static function init(string $dir, Request $request = null, string $userTable = '_geUser'): App {
        self::initEnv();

        header('Content-Type: text/html; charset=utf-8');
        header_remove("X-Powered-By");

        if (!isset(self::$req)) self::errorPage(500, 'G app initialization', __METHOD__ . ':' . __LINE__ . ' Initialize G::$req = new Request() before G::init()');
        if (isset(self::$app)) self::errorPage(500, 'G app initialization', __METHOD__ . ':' . __LINE__ . ' App was already initialized');
        if (!$dir) self::errorPage(500, 'G app initialization', __METHOD__ . ':' . __LINE__ . ' $dir is empty');
        if (!is_dir($dir)) self::errorPage(500, 'G app initialization', __METHOD__ . ':' . __LINE__ . ' $dir is not a directory');
        if (!file_exists($dir . '/config/app.php')) self::errorPage(500, 'G app initialization', __METHOD__ . ':' . __LINE__ . ' config/app.php');

        libxml_use_internal_errors(true);

        if ($request) self::$req = $request;
        self::$me = new User($userTable);

        $app = new App($dir);

        include $app->dir . 'config/app.php';
        if (file_exists($app->dir . 'config/app.private.php')) include $app->dir . 'config/app.private.php';

        self::$app = $app;
        self::timerStart('Total', $_SERVER['REQUEST_TIME_FLOAT']);

        return self::$app;
    }




    static function initCLI(string $dir, Request $request = null, string $userTable = '_geUser'): App {
        self::initEnv();

        if (!isset(self::$req)) self::errorPage(500, 'G app CLI initialization', __METHOD__ . ':' . __LINE__ . ' Initialize G::$req = new Request() before G::initCLI()');
        if (isset(self::$app)) self::errorPage(500, 'G app CLI initialization', __METHOD__ . ':' . __LINE__ . ' App was already initialized');
        if (!$dir || !is_dir($dir)) self::errorPage(500, 'G app CLI initialization', __METHOD__ . ':' . __LINE__ . ' $dir is not a directory');
        if (!file_exists($dir . '/config/app.php')) self::errorPage(500, 'G app CLI initialization', __METHOD__ . ':' . __LINE__ . ' App config not found');

        libxml_use_internal_errors(true);

        if ($request) self::$req = $request;
        self::$me = new User($userTable);

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
            self::errorPage($e->getCode(), $e->getMessage(), $e->getTraceAsString());
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
        if (!isset(self::$app)) self::errorPage(500, 'G editor initialization', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        if (isset(self::$editor)) self::errorPage(500, 'G editor initialization', __METHOD__ . ':' . __LINE__ . ' Editor was already initialized');
        if (!$dir || !is_dir($dir)) self::errorPage(500, 'G editor initialization', __METHOD__ . ':' . __LINE__ . ' $dir is not a directory');

        self::$editor = new Editor($dir);

        return self::$editor;
    }




    static function isDevEnv(): bool {
        return getenv('GALAXIA_ENV') === 'development';
    }




    static function isDev(): bool {
        if (!isset(self::$me)) return false;
        if (!self::$me->loggedIn) return false;

        return self::$me->hasPerm('dev');
    }


    static function isDevDebug(): bool {
        if (!self::isDev()) return false;
        if (!isset(self::$app) || !self::$app->cookieDebugVal || !self::$app->cookieDebugKey || !isset($_COOKIE)) return false;

        return ($_COOKIE[self::$app->cookieDebugKey] ?? null) === self::$app->cookieDebugVal;
    }



    static function isCli(): bool {
        return php_sapi_name() == 'cli';
    }



    static function insideEditor(): bool {
        if (!isset(self::$editor)) return false;

        return ($_SERVER['DOCUMENT_ROOT'] ?? null) === self::$editor->dir . 'public';
    }




    static function getMysqli(): mysqli {
        if (!isset(self::$app)) self::errorPage(500, 'G db', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        if (!isset(self::$mysqli)) {
            self::timerStart('DB Connection');

            self::$mysqli = new mysqli(self::$app->mysqlHost, self::$app->mysqlUser, self::$app->mysqlPass, self::$app->mysqlDb);
            if (self::$mysqli->connect_errno) {
                self::errorPage(500, 'G db Connection Failed' . __METHOD__ . ':' . __LINE__ . ' ' . self::$mysqli->connect_errno);
            }

            if (self::isDevEnv() || self::isDev()) mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            self::$mysqli->set_charset('utf8mb4');
            self::$mysqli->query('SET time_zone = ' . Text::q(self::$app->timeZone) . ';');
            self::$mysqli->query('SET lc_time_names = ' . Text::q(self::$app->locale['long']) . ';');

            self::timerStop('DB Connection');
        }

        return self::$mysqli;
    }




    static function loadTranslations() {
        self::timerStart('Translations');

        if (isset(self::$editor) && file_exists(self::$editor->dir . 'src/GalaxiaEditor/config/translation.php'))
            Text::$translation = array_merge(Text::$translation, include(self::$editor->dir . 'src/GalaxiaEditor/config/translation.php'));

        if (isset(self::$app) && file_exists(self::$app->dir . 'config/translation.php'))
            Text::$translation = array_merge(Text::$translation, include(self::$app->dir . 'config/translation.php'));

        if (isset(self::$app) && file_exists(self::$app->dir . 'config/translationAlias.php'))
            Text::$translationAlias = array_merge(Text::$translationAlias, include(self::$app->dir . 'config/translationAlias.php'));

        self::timerStop('Translations');
    }




    // timing

    static function timerStart(string $timerLabel, $timeFloat = null) {
        // if (isset(self::$me) && !self::isDev()) return;

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
        // if (isset(self::$me) && !self::isDev()) return;

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


    static function timerPrint(
        bool $comments = false,
        bool $memory = false,
        bool $includes = false
    ) {
        if (!self::isCli() && !self::isDevEnv() && !self::isDev()) return;

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
        $padHead = ' ';
        $pad     = ' ';

        if ($comments) {
            $prefix  = '<!-- ';
            $postfix = ' -->' . PHP_EOL;
            $pad     = '`';
            $padHead = '.';
        }

        $timeTotal   = self::$timers['Total']['total'];
        $levelTotals = [$timeTotal];

        $cols   = [];
        $colLen = [];

        foreach (self::$timers as $timerLabel => $time) {
            $percentOfParent             = '';
            $levelTotals[$time['level']] = $time['total'];

            if ($time['level'] > 0) {
                $divisor = $levelTotals[$time['level'] - 1];
                if ($divisor == 0) $divisor = 1;
                $percentOfParent = (($time['total'] * 100) / $divisor);
                $percentOfParent = number_format($percentOfParent);
            }

            // if ($percentOfParent > 99) $percentOfParent = 99;

            $cols[$timerLabel]['start'] = number_format(($time['start'] - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 3);
            $cols[$timerLabel]['#']     = $time['count'];
            if ($time['running']) {
                $cols[$timerLabel]['time'] = number_format(($timeEnd - $time['start']) * 1000, 2);
            } else {
                $cols[$timerLabel]['time'] = number_format($time['total'] * 1000, 2);
            }

            $cols[$timerLabel]['mem'] = Text::bytesIntToAbbr($time['mem'], 2, '.');
            $cols[$timerLabel]['%']   = number_format((($time['total'] * 100) / $timeTotal), 2);

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
                $r .= str_pad($col ?? '', $len, $padHead, STR_PAD_LEFT) . ' ';
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

        $memEnd      = ' ' . Text::bytesIntToAbbr(memory_get_usage(false), 2, $padHead);
        $memPeak     = ' ' . Text::bytesIntToAbbr(memory_get_peak_usage(false), 2, $padHead);
        $memEndReal  = ' ' . Text::bytesIntToAbbr(memory_get_usage(true), 2, $padHead);
        $memPeakReal = ' ' . Text::bytesIntToAbbr(memory_get_peak_usage(true), 2, $padHead);
        $incFiles    = ' ' . count(get_included_files());
        $memLenMax   = ' ' . max(strlen($memEnd), strlen($memPeak), strlen($memEndReal), strlen($memPeakReal), strlen($incFiles));

        if ($memory) {
            $r .= $prefix . 'mem at end ......' . str_pad($memEnd, $memLenMax, $padHead, STR_PAD_LEFT) . $postfix;
            $r .= $prefix . 'mem peak ........' . str_pad($memPeak, $memLenMax, $padHead, STR_PAD_LEFT) . $postfix;
            $r .= $prefix . 'mem real at end .' . str_pad($memEndReal, $memLenMax, $padHead, STR_PAD_LEFT) . $postfix;
            $r .= $prefix . 'mem real peak ...' . str_pad($memPeakReal, $memLenMax, $padHead, STR_PAD_LEFT) . ' / ' . ini_get('memory_limit') . $postfix;
        }
        $r .= $prefix . 'included files ..' . str_pad($incFiles, $memLenMax, $padHead, STR_PAD_LEFT) . $postfix;
        if ($includes) {
            $r         .= $prefix . ' ... ' . $postfix;
            $parentDir = dirname(self::$app->dir);
            foreach (get_included_files() as $file) {
                if (str_starts_with($file, self::$app->dir)) $file = './' . substr($file, strlen(self::$app->dir));
                else if (str_starts_with($file, $parentDir)) $file = '..' . substr($file, strlen($parentDir));
                $r .= $prefix . $file . $postfix;
            }
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

        $errors = [
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
        ];

        if (!in_array($code, [403, 404, 500])) $code = 500;
        http_response_code($code);

        if (self::isCli()) {
            echo "$codeOriginal - $msg" . PHP_EOL;
            echo(' ' . $debugText . PHP_EOL);
            if (self::isDev()) {
                db();
            }
            exit();
        }

        if (self::insideEditor()) {
            if (isset(self::$me) && self::$me->loggedIn) {
                $errorCode = $code;
                $error     = $errors[$code] . '<br><br>';
                // if (self::isDev()) {
                $error .= 'Original error code: ' . $codeOriginal . '<br>';
                $error .= nl2br($msg) . '<br><br>';
                $error .= nl2br($debugText) . '<br>';
                // }

                include self::$editor->dirLayout . 'layout-error.phtml';
                exit();
            }
        }

        $errorFile = '';
        if (isset(self::$app)) {
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


        if ($code != 404) {
            if (self::isDevEnv()) {
                d($msg);
                d($debugText);
                db();
            } else {
                error_log($msg . ' - ' . $debugText);
            }
        }

        // self::timerPrint(true, true, true);
        exit();
    }


    #[NoReturn]
    static function redirect($location = '', int $code = 302): void {
        $location = Text::h(trim($location, "/ \t\n\r\0\x0B"));
        if (self::isCli()) {
            echo "$code - /$location" . PHP_EOL;
            exit();
        } else if (headers_sent()) {
            echo 'headers already sent. redirect: <a href="' . $location . '">' . $location . '</a>' . PHP_EOL;
            exit();
        }

        header('Location: /' . $location, true, $code);
        exit();
    }



    static function test(
        string $script, array $tests, string $host, int $argc, callable $fBuild, callable $fTest
    ): void {
        if ($argc == 1) {
            $testsPassed = 0;
            $testsTotal  = count($tests);

            if ($argc < 3) echo 'Testing ' . $host . " ($testsTotal)" . PHP_EOL;

            $fBuild();

            foreach ($tests as $url => $code) {
                $cmd    = escapeshellcmd("php $script $url");
                $result = shell_exec($cmd) ?? '';

                if (str_starts_with($result, $code)) {
                    $testsPassed++;
                    continue;
                }

                echo 'Error: ' . $url . " -- expected: $code -- returned: $result";
            }

            $prefix = ($testsPassed == count($tests)) ? '[OK]' : '[FAIL]';

            exit("$prefix $testsPassed/$testsTotal tests passed." . PHP_EOL);
        }

        if ($argc == 2) {
            $page = $fTest();
            // echo $page;
            if (empty(trim($page))) self::errorPage(500, 'Empty.');
            self::errorPage(200, 'OK.');
        }

        echo 'Usage:' . PHP_EOL;
        echo 'run tests: php test.php' . PHP_EOL;
        echo 'test single page: php test.php /example-url' . PHP_EOL;

        exit();
    }



    static function dir(): string {
        return self::$app->dir;
    }

    static function dirLog(): string {
        return self::$app->dirLog;
    }

    static function dirCache(): string {
        return self::$app->dirCache;
    }

    static function dirImage(): string {
        return self::$app->dirImage;
    }




    static function langSet(string $lang = null): void {
        if (is_null($lang) || !isset(self::$app->locales[$lang])) $lang = self::$app->lang;
        self::$app->lang   = $lang;
        self::$app->locale = self::$app->locales[self::$app->lang];
        self::$app->langs  = array_keys(self::$app->locales);

        $key = array_search(self::$app->lang, self::$app->langs);
        if ($key > 0) {
            unset(self::$app->langs[$key]);
            array_unshift(self::$app->langs, self::$app->lang);
        }

        setlocale(LC_TIME, self::$app->locale['long'] . '.UTF-8');
        date_default_timezone_set(self::$app->timeZone);
    }

    static function langSetFromUrl(): void {
        self::langSet(self::$req->langFromUrl());
    }

    static function langAddInactive(): void {
        foreach (self::$app->localesInactive as $lang => $locale) {
            if (isset(self::$app->locales[$lang])) continue;
            self::$app->locales[$lang] = $locale;
            self::$app->langs          = array_keys(self::$app->locales);
        }
    }

    static function lang(): string {
        return self::$app->lang;
    }

    static function langs(): array {
        return self::$app->langs;
    }

    static function locale(): array {
        return self::$app->locale;
    }

    static function locales(): array {
        return self::$app->locales;
    }

    static function addLangPrefix(string $url, string $lang = ''): string {
        $url = trim($url, '/');
        if (!isset(self::$app->locales[$lang])) $lang = self::$app->lang;
        if ($url == '') return self::$app->locales[$lang]['url'];

        return Text::h(rtrim(self::$app->locales[$lang]['url'], '/') . '/' . $url);
    }




    static function cache(
        string $scope, int $level, string $key,
        callable $f, bool $bypass = null, bool $write = null
    ): array {
        return AppCache::get(self::dirCache(), $scope, $level, $key, $f, $bypass, $write);
    }

    static function cacheDelete($scopes, $key = '*'): void {
        AppCache::delete(self::dirCache(), $scopes, $key);
    }

    static function cacheDeleteAll(): void {
        AppCache::deleteAll(self::dirCache());
    }

    static function cacheDeleteOld(): void {
        AppCache::deleteOld(self::dirCache());
    }




    static function image($img, $extra = ''): string {
        return AppImage::render($img, $extra);
    }

    static function imageGet($imgSlug, $img = [], $resize = true): array {
        return AppImage::imageGet($imgSlug, $img, $resize);
    }

    static function imageUpload(array $files, $replaceDefault = false, int $toFitDefault = 0, string $type = ''): array {
        return AppImage::imageUpload($files, $replaceDefault, $toFitDefault, $type);
    }




    static function prepare(string $query) {
        return self::getMysqli()->prepare($query);
    }

    static function execute(string $query, $types = '', ...$vars) {
        $stmt = self::getMysqli()->prepare($query);
        if ($types) {
            $stmt->bind_param($types, ...$vars);
        }
        $stmt->execute();
        return $stmt;
    }




    static function routeList(int $pageMinStatus, $pageSlug = 'pgSlug'): array {
        return AppRoute::list($pageMinStatus, $pageSlug);
    }

    static function routeSlugToId(string $table, string $status, string $tableSlug, bool $redirect, string $matchSlug, array $langs = null): ?int {
        return AppRoute::slugToId($table, $status, $tableSlug, $redirect, $matchSlug, $langs ?? self::langs());
    }

    static function routeSitemap(string $schemeHost): void {
        AppRoute::generateSitemap($schemeHost);
    }


    static function login(): void {
        self::$me->logInFromCookieSessionId(self::$app->cookieEditorKey);
    }

    static function isLoggedIn(): bool {
        return self::$me->loggedIn ?? false;
    }

    public static function versionQuery(): string {
        if (G::$req->cacheBypass) {
            return '?ver=' . time();
        } else if (file_exists(G::dir() . '.git/refs/heads/main')) {
            $gitHash = file_get_contents(G::dir() . '.git/refs/heads/main');
            return '?ver=' . substr($gitHash, 8, 5);
        } else {
            return '?ver=' . date('Y-m');
        }
    }

}
