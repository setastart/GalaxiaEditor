<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


use mysqli;
use mysqli_result;
use mysqli_stmt;
use Throwable;
use function debug_backtrace;
use function dirname;
use function file_exists;
use function preg_match;


class G {

    public static Request $req;
    public static App     $app;
    public static Editor  $editor;
    public static User    $me;
    public static AppMeta $meta;

    private static mysqli    $mysqli;
    private static ?RedisCli $redis;
    private static bool      $redisFailed = false;

    static array $explains = [];

    public static int    $errorCode = 0;
    public static string $error     = '';


    static function init(string $dir, string $userTable = '_geUser'): void {
        self::initEnv();

        if (!self::isCli()) {
            header('Content-Type: text/html; charset=utf-8');
            header_remove("X-Powered-By");
        }

        if (!isset(self::$req)) self::errorPage(500, 'G app initialization', __METHOD__ . ':' . __LINE__ . ' Initialize G::$req = new Request() before G::init()');
        if (isset(self::$app)) self::errorPage(500, 'G app initialization', __METHOD__ . ':' . __LINE__ . ' App was already initialized');

        libxml_use_internal_errors(true);

        self::$me  = new User($userTable);
        self::$app = new App($dir);

        AppTimer::start('Total', $_SERVER['REQUEST_TIME_FLOAT']);

        require_once self::$app->dir . 'autoload.php';
        require self::$app->dir . 'config/app.php';
        include self::$app->dir . 'config/app.private.php';

    }




    private static function initEnv(): void {
        error_reporting(E_ALL);

        set_exception_handler(function(Throwable $e) {
            self::errorPage(
                $e->getCode(),
                'exc ' . $e->getFile() . ':' . $e->getLine() . ' ' . $e->getMessage(),
                $e->getTraceAsString()
            );
        });

        set_error_handler(function($code, $msg, $file = '', $line = 0) {
            if ($msg == 'fsockopen(): Unable to connect to localhost:6379 (Connection refused)') {
                return true;
            }
            self::errorPage($code, 'error ' . $msg, $file . ':' . $line);
        });

        register_shutdown_function(function() {
            if (G::isDev() && isset(G::$editor) && G::$editor->layout != 'layout-none') {
                AppTimer::print(true, true);
            }
            session_write_close();


            $error = error_get_last();
            if ($error !== null) {
                self::errorPage(500, 'shutdown error ' . $error['message'], $error['type'] . ' - ' . $error['file'] . ':' . $error['line']);
            }
            exit();
        });

        if (self::isDevEnv() || self::isCli()) {
            if (self::isDevEnv()) {
                putenv("PATH=" . getenv('PATH') . ':/opt/homebrew/bin');
            }
            ini_set('display_errors', '1');
        } else {
            ini_set('display_errors', '0');
        }
    }




    static function initEditor(): void {
        if (!isset(self::$app)) self::errorPage(500, 'G editor initialization', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        if (isset(self::$editor)) return;

        $dir = dirname(__DIR__, 3) . '/';

        require_once $dir . 'src/autoload-editor.php';

        self::$editor = new Editor($dir);

        self::$editor->version = self::cacheString(
            scope: 'editor',
            level: 0,
            key: 'version',
            f: function() use ($dir): string {
                $ver = shell_exec("cd $dir; " . 'git log --oneline -n 1 --pretty=format:%s');
                if (preg_match('/GalaxiaEditor Version ([\d.]+)/', $ver, $matches)) {
                    $ver = $matches[1] ?? 'Unknown';
                }
                return $ver;
            }
        );
    }




    static function login(): void {
        self::$me->logInFromCookieSessionId(self::$app->cookieEditorKey);

        if (G::isLoggedIn()) {
            G::$req->minStatus       = 1;
            G::$req->cacheBypassHtml = true;
            if (G::isDev()) {
                G::$req->cacheWrite = false;
            }
            if (G::isDevDebug()) {
                G::$req->cacheBypass = true;
            }
        }
    }

    static function isLoggedIn(): bool {
        return self::$me->loggedIn ?? false;
    }

    static function isCli(): bool {
        return PHP_SAPI == 'cli';
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
        if (!isset(self::$app) || !self::$app->cookieDebugVal || !self::$app->cookieDebugKey) return false;
        if (!isset($_COOKIE) || !isset($_COOKIE[self::$app->cookieDebugKey])) return false;

        return $_COOKIE[self::$app->cookieDebugKey] === self::$app->cookieDebugVal;
    }

    static function isInsideEditor(): bool {
        if (!isset(self::$editor)) return false;

        return ($_SERVER['DOCUMENT_ROOT'] ?? null) === self::$editor->dir . 'public';
    }

    static function isTest(): bool {
        return self::$req->test;
    }



    static function mysqli(): mysqli {
        if (!isset(self::$app)) self::errorPage(500, 'G db', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        if (!isset(self::$mysqli)) {
            AppTimer::start('DB Connection');

            self::$mysqli = new mysqli(self::$app->mysqlHost, self::$app->mysqlUser, self::$app->mysqlPass, self::$app->mysqlDb);
            if (self::$mysqli->connect_errno) {
                self::errorPage(500, 'G db Connection Failed' . __METHOD__ . ':' . __LINE__ . ' ' . self::$mysqli->connect_errno);
            }

            if (self::isDevEnv() || self::isDev()) mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            self::$mysqli->set_charset('utf8mb4');
            self::$mysqli->query('SET time_zone = ' . Text::q(self::$app->timeZone) . ';');
            self::$mysqli->query('SET lc_time_names = ' . Text::q(self::$app->locale['long']) . ';');

            AppTimer::stop('DB Connection');
        }

        return self::$mysqli;
    }




    /**
     * Use with null safe operator ?-> like G::redis()?->cmd('PING')->get();
     */
    static function redis(): ?RedisCli {
        if (!isset(self::$app)) self::errorPage(500, 'G redis', __METHOD__ . ':' . __LINE__ . ' App was not initialized');
        if (!isset(self::$redis) && !self::$redisFailed) {
            AppTimer::start('Redis Connection');

            self::$redis = new RedisCli(host: 'localhost', port: '6379');

            if (is_resource(self::$redis->handle)) {
                self::$redis->setErrorFunction(function($error) {
                    self::errorPage(500, 'G redis Error' . __METHOD__ . ':' . __LINE__ . ' ' . $error);
                });
            } else {
                self::$redisFailed = true;
                AppTimer::mark('Redis Connection Failed');
                Flash::devlog('Redis Connection Failed');
                self::$redis = null;
            }

            AppTimer::stop('Redis Connection');
        }

        return self::$redis;
    }




    static function loadTranslations(bool $withEditor = false): void {
        AppTimer::start('Translations');

        if ($withEditor) {
            Text::$translation = self::cacheArray(
                scope: 'app',
                level: 1,
                key: 'translation-with-editor',
                f: function(): array {
                    return array_merge(
                        include dirname(__DIR__, 2) . '/GalaxiaEditor/config/translation.php',
                        include self::$app->dir . 'config/translation.php',
                    );
                },
                bypass: self::$req->cacheBypass,
                write: self::$req->cacheWrite,
            );
        } else {
            Text::$translation = self::cacheArray(
                scope: 'app',
                level: 1,
                key: 'translation-without-editor',
                f: fn(): array => include self::$app->dir . 'config/translation.php',
                bypass: self::$req->cacheBypass,
                write: self::$req->cacheWrite,
            );
        }

        Text::$translationAlias = self::cacheArray(
            scope: 'app',
            level: 1,
            key: 'translationAlias',
            f: fn(): array => include self::$app->dir . 'config/translationAlias.php',
            bypass: self::$req->cacheBypass,
            write: self::$req->cacheWrite,
        );

        AppTimer::stop('Translations');
    }




    static function errorPage(int $code, string $msg = '', string $debugText = ''): never {
        $codeOriginal = $code;
        $errors       = [
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
        ];

        if (self::isCli()) {
            ob_get_clean();
            echo "$codeOriginal - $msg" . PHP_EOL;
            echo(' ' . $debugText . PHP_EOL);
            if (self::isDev()) {
                db();
            }
            exit(1);
        }

        if (!in_array($code, [403, 404, 500])) $code = 500;
        http_response_code($code);

        if (isset(self::$me) && self::$me->loggedIn && self::isInsideEditor()) {
            self::$errorCode = $code;
            self::$error     = $errors[$code] . '<br><br>';
            // if (self::isDev()) {
            self::$error .= 'Original error code: ' . $codeOriginal . '<br>';
            self::$error .= nl2br($msg, false) . '<br><br>';
            self::$error .= nl2br($debugText, false) . '<br>';
            // }

            error_log($msg . ' - ' . $debugText);
            include self::$editor->dirLayout . 'layout-error.phtml';
            exit();
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


        if (self::isDevEnv()) {
            if (!self::isCli()) echo '<!-- ' . PHP_EOL;
            s($msg, $codeOriginal, $code, $errors[$code]);
            s($debugText);
            db();
            if (!self::isCli()) echo '-->' . PHP_EOL;
        } else {
            if ($code == 500) {
                error_log($msg . ' - ' . $debugText);
            }
        }

        // AppTimer::print(true, true, true);
        exit();
    }


    static function redirect($location = '', int $code = 302): never {
        $location = Text::h(trim($location, "/ \t\n\r\0\x0B"));
        // if (strlen($location) == 2) $location .= '/';

        if (self::isCli()) {
            echo "$code - /$location" . PHP_EOL;
            exit();
        }

        if (headers_sent()) {
            echo 'headers already sent. redirect: <a href="' . $location . '">' . $location . '</a>' . PHP_EOL;
            exit();
        }

        header('Location: /' . $location, true, $code);
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



    /** @deprecated - Use AppCache */
    static function cacheArray(
        string   $scope, int $level, string $key,
        callable $f, bool $bypass = null, bool $write = null
    ): array {
        return AppCache::cacheArray(
            scope: $scope,
            level: $level,
            key: $key,
            f: $f,
            load: !($bypass ?? false),
            save: $write ?? true,
            dirCache: self::dirCache()
        );
    }

    /** @deprecated - Use AppCache */
    static function cacheString(
        string   $scope, int $level, string $key,
        callable $f, bool $bypass = null, bool $write = null
    ): string {
        return AppCache::cacheString(
            scope: $scope,
            level: $level,
            key: $key,
            f: $f,
            load: !($bypass ?? false),
            save: $write ?? true,
            dirCache: self::dirCache()
        );
    }




    /** @deprecated - Use G::execute() */
    static function prepare(string $query): false|mysqli_stmt {
        return self::mysqli()->prepare($query);
    }

    static function execute(string $query, array $params = null): false|mysqli_result {
        if (G::isDevEnv() && G::isDevDebug() && preg_match("~^\W*select\W~i", $query)) {
            G::$explains[] = G::explain($query, $params);
        }
        $stmt = self::mysqli()->prepare($query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    static function explain(string $query, array $params = null): array {
        $db     = debug_backtrace();
        $source = $db[1]['file'] . ':' . $db[1]['line'];

        $r = [
            'src'     => $source,
            'query'   => $query,
            'params'  => $params,
            'explain' => [],
        ];

        $stmt = self::mysqli()->prepare('EXPLAIN ' . $query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $stmt->close();

        while ($data = $result->fetch_assoc()) {
            $r['explain'][] = $data;
        }

        return $r;
    }




    static function versionQuery(): string {
        if (G::isTest()) {
            return '?ver=test';
        }

        if (G::$req->cacheBypass || G::isDevEnv()) {
            return '?ver=' . $_SERVER['REQUEST_TIME'];
        }

        if (file_exists(G::dir() . '.git/refs/heads/main')) {
            $gitHash = file_get_contents(G::dir() . '.git/refs/heads/main');
            return '?ver=' . substr($gitHash, 8, 5);
        }

        return '?ver=' . date('Y-m');
    }

}
