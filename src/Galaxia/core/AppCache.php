<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


use function file_exists;
use function file_get_contents;

class AppCache {

    private static bool $saveSkip = false; // For nested caches. Skip saving outer cache if inner fails.

    static function cacheString(
        string   $scope,
        int      $level,
        string   $key,
        callable $f,
        bool     $load = true,
        bool     $save = true,
        ?string  $dirCache = null,
        bool     $debug = false,
    ): string {
        $dirCache ??= G::$app->dirCache;

        $subdir = 'app';
        if ($scope == 'editor') $subdir = 'editor';

        $dir = $dirCache . trim($subdir, '/') . '/';
        if (!is_dir($dir)) mkdir($dir);

        $cacheName = "{$scope}-{$level}-{$key}.string";
        $cacheFile = $dir . $cacheName . '.cache';

        $fCache = function() use ($debug, $dirCache, $cacheName, $cacheFile, $f, $load, $save): string {
            AppTimer::start($cacheName);
            $r = "";
            $s = "Cache str";
            if ($load && $save) $s .= " RW"; else if ($load) $s .= " R";
            else if ($save) $s .= " W";

            $loaded = false;
            if ($load) {
                $s .= " Load";
                $r = (file_exists($cacheFile)) ? file_get_contents($cacheFile) : false;
                if ($r === false) {
                    $s .= " MISS.";
                } else {
                    $loaded = true;
                }
            }

            if (!$loaded) {
                $s .= " Compute";
                $r = $f();
            }

            if (is_string($r)) {
                $s .= " OK.";
            } else {
                $s              .= " FAIL.";
                $r              = "";
                self::$saveSkip = true;
            }

            if ($save && !$loaded) {
                $s .= " Save";
                if (self::$saveSkip) {
                    $s .= " SKIP.";
                } else {
                    $s .= file_put_contents($cacheFile, $r) === false ? " FAIL." : " OK.";
                }
            }

            $s .= " $cacheName";
            AppTimer::stop($cacheName, rename: $s);
            if ($debug) {
                Flash::info($s);
            }
            return $r;
        };

        return self::lock($cacheName, $fCache);
    }

    static function cacheArray(
        string   $scope,
        int      $level,
        string   $key,
        callable $f,
        bool     $load = true,
        bool     $save = true,
        ?string  $dirCache = null
    ): array {
        $dirCache ??= G::$app->dirCache;

        $subdir = 'app';
        if ($scope == 'editor') $subdir = 'editor';

        $dir = $dirCache . trim($subdir, '/') . '/';
        if (!is_dir($dir)) mkdir($dir);

        $cacheName = "{$scope}-{$level}-{$key}.array";
        $cacheFile = $dir . $cacheName . '.cache';

        $fCache = function() use ($dirCache, $cacheName, $cacheFile, $f, $load, $save): array {
            AppTimer::start($cacheName);
            $r = [];
            $s = "Cache arr";
            if ($load && $save) $s .= " RW"; else if ($load) $s .= " R";
            else if ($save) $s .= " W";

            $loaded = false;
            if ($load) {
                $s .= " Load";
                $r = (file_exists($cacheFile)) ? include $cacheFile : false;
                if ($r === false) {
                    $s .= " MISS.";
                } else {
                    $loaded = true;
                }
            }

            if (!$loaded) {
                $s .= " Compute";
                $r = $f();
            }

            if (is_array($r)) {
                $s .= " OK.";
            } else {
                $s              .= " FAIL.";
                $r              = [];
                self::$saveSkip = true;
            }

            if ($save && !$loaded) {
                $s .= " Save";
                if (self::$saveSkip) {
                    $s .= " SKIP.";
                } else {
                    $s .= file_put_contents($cacheFile, '<?php return ' . var_export($r, true) . ';') === false ? " FAIL." : " OK.";
                }
            }

            $s .= " - $cacheName";
            AppTimer::stop($cacheName, rename: $s);
            return $r;
        };

        return self::lock($cacheName, $fCache);
    }


    static function delete(
        array   $scopes,
        string  $key = '*',
        ?string $dirCache = null
    ): void {
        $dirCache ??= G::$app->dirCache;

        $dirCacheStrlen = strlen($dirCache);
        if (in_array('editor', $scopes) && !in_array('app', $scopes)) $scopes[] = 'app';
        $files = [];
        foreach ($scopes as $scope) {
            $dir = 'app/';
            if ($scope == 'editor') $dir = 'editor/';

            $cacheName = $scope . '-*-' . $key;
            $pattern   = $dirCache . $dir . $cacheName . '.cache';
            $glob      = glob($pattern, GLOB_NOSORT);
            foreach ($glob as $file) {
                if (isset($files[$file])) continue;
                preg_match('~^\w+-(\d+)-~', substr($file, $dirCacheStrlen), $matches);
                $files[$file] = $matches[1] ?? '999';
            }
        }
        asort($files, SORT_NUMERIC);

        $deleted = 0;
        $total   = 0;
        foreach ($files as $fileName => $level) {
            if (unlink($fileName)) $deleted++;
            $total++;
        }

        Flash::devlog(implode(', ', $scopes) . ': caches deleted: ' . $deleted . '/' . $total);

        $pattern = $dirCache . 'editor/list-history-*.cache';
        $glob    = glob($pattern, GLOB_NOSORT);
        foreach ($glob as $fileName) unlink($fileName);

        if (is_dir($dirCache . 'nginx/')) {
            $glob    = glob($dirCache . 'nginx/*/*/*');
            $deleted = 0;
            $total   = 0;
            foreach ($glob as $fileName) {
                if (unlink($fileName)) $deleted++;
                $total++;
            }
            if ($deleted) Flash::devlog('nginx caches deleted: ' . $deleted . '/' . $total);
        }
        if (is_dir($dirCache . 'nginxAjax/')) {
            $glob    = glob($dirCache . 'nginxAjax/*/*/*');
            $deleted = 0;
            $total   = 0;
            foreach ($glob as $fileName) {
                if (unlink($fileName)) $deleted++;
                $total++;
            }
            if ($deleted) Flash::devlog('nginxAjax caches deleted: ' . $deleted . '/' . $total);
        }
    }


    static function deleteAll(?string $dirCache = null): void {
        $dirCache       ??= G::$app->dirCache;
        $dirCacheStrlen = strlen($dirCache);
        $files          = [];

        $glob = glob($dirCache . 'app/*.cache', GLOB_NOSORT);
        foreach ($glob as $file) {
            preg_match('~^\w+-(\d+)-~', substr($file, $dirCacheStrlen), $matches);
            $files[$file] = $matches[1] ?? '999';
        }
        $glob = glob($dirCache . 'editor/*.cache', GLOB_NOSORT);
        foreach ($glob as $file) {
            preg_match('~^\w+-(\d+)-~', substr($file, $dirCacheStrlen), $matches);
            $files[$file] = $matches[1] ?? '999';
        }

        asort($files, SORT_NUMERIC);

        $deleted = 0;
        $total   = 0;
        foreach ($files as $fileName => $level) {
            if (unlink($fileName)) $deleted++;
            $total++;
        }

        Flash::devlog('ALL caches deleted: ' . $deleted . '/' . $total);

        if (is_dir($dirCache . 'nginx/')) {
            $glob    = glob($dirCache . 'nginx/*/*/*');
            $deleted = 0;
            $total   = 0;
            foreach ($glob as $fileName) {
                if (unlink($fileName)) $deleted++;
                $total++;
            }
            if ($deleted) Flash::devlog('nginx caches deleted: ' . $deleted);
        }
        if (is_dir($dirCache . 'nginxAjax/')) {
            $glob    = glob($dirCache . 'nginxAjax/*/*/*');
            $deleted = 0;
            $total   = 0;
            foreach ($glob as $fileName) {
                if (unlink($fileName)) $deleted++;
                $total++;
            }
            if ($deleted) Flash::devlog('nginxAjax caches deleted: ' . $deleted);
        }
    }


    static function deleteOld(?string $dirCache = null): void {
        $dirCache ??= G::$app->dirCache;

        $pattern = $dirCache . '*.cache';
        $glob    = glob($pattern, GLOB_NOSORT);

        $now     = time();
        $old     = 60 * 60 * 24 * 3; // 3 days
        $deleted = 0;

        foreach ($glob as $fileName)
            if (is_file($fileName))
                if ($now - filemtime($fileName) >= $old)
                    if (unlink($fileName)) $deleted++;

        Flash::devlog('App old caches deleted: ' . $deleted);
    }


    static function route(
        callable $f
    ): array {
        return self::cacheArray(
            scope: 'app',
            level: 1,
            key: "route-primary-" . G::$req->minStatus,
            f: $f,
            load: true,
            save: true,
            dirCache: G::$app->dirCache
        );
    }

    static function subpage(
        callable $f,
        string   $table
    ): array {
        return self::cacheArray(
            scope: 'app',
            level: 5,
            key: "route-subpage-$table-" . G::$req->minStatus,
            f: $f,
            load: !G::$req->cacheBypass,
            save: G::$req->cacheWrite,
            dirCache: G::$app->dirCache
        );
    }

    static function subpageLang(
        callable $f,
        string   $table
    ): array {
        return self::cacheArray(
            scope: 'app',
            level: 5,
            key: "route-subpageLang-$table-" . G::$req->minStatus,
            f: $f,
            load: !G::$req->cacheBypass,
            save: G::$req->cacheWrite,
            dirCache: G::$app->dirCache
        );
    }


    static function lock(
        string   $name,
        callable $f
    ): mixed {
        $dir = G::dirCache() . 'flock';
        if (!is_dir($dir)) {
            if (!mkdir($dir)) {
                return $f();
            }
        }
        $name .= '.lock';

        if ($fp = fopen($dir . '/' . $name, 'w')) {
            try {
                flock($fp, LOCK_EX);
                $r = $f();
            } finally {
                fclose($fp);
            }
        } else {
            Flash::error("Failed to open cache file " . $dir . "/" . $name);
            $r = $f();
        }

        return $r;
    }


    static function redisLock(
        string   $name,
        callable $f,
        int      $ttl = 30 // in seconds
    ): mixed {

        if (G::redis() == null) {
            return self::lock($name . '.lock', $f);
        }

        $name      = G::$app->mysqlDb . ':lock:' . $name;
        $token     = uniqid();
        $usleepMin = 64;
        $usleepMax = 100000;
        $usleep    = $usleepMin;

        try {
            while (!self::redisLockAcquire(name: $name, token: $token, ttl: $ttl * 1000)) {
                usleep(mt_rand(16, $usleep));
                $usleep *= 2;
                $usleep = min($usleep, $usleepMax);
            }
            $r = $f();
        } finally {
            self::redisLockRelease(name: $name, token: $token);
        }

        return $r;
    }

    static function redisLockAcquire(string $name, $token, $ttl): bool {
        return (G::redis()?->cmd('SET', $name, $token, 'NX', 'PX', $ttl)->set()[0] ?? '') === 'OK';
    }

    static function redisLockRelease(string $name, $token): void {
        $lua = 'if redis.call("GET", ARGV[1]) == ARGV[2] then return redis.call("DEL", ARGV[1]) else return 0 end';
        G::redis()?->cmd('EVAL', $lua, 0, $name, $token)->set();
    }


}
