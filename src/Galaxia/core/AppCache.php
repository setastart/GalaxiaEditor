<?php
// Copyright 2017-2023 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


use function file_exists;
use function file_get_contents;

class AppCache {

    static function arrayDir(
        string   $scope,
        int      $level,
        string   $key,
        callable $f,
        bool     $bypass = null,
        bool     $write = null,
        string   $dirCache = null
    ): array {
        $dirCache ??= G::$app->dirCache;

        $result = [];
        $subdir = 'app';
        if ($scope == 'editor') $subdir = 'editor';

        $cacheName = $scope . '-' . $level . '-' . $key;

        $dir = $dirCache . trim($subdir, '/') . '/';
        if (!is_dir($dir)) mkdir($dir);

        if (is_null($write)) $write = !$bypass;
        if (!$bypass) $write = true;

        $cacheFile = $dir . $cacheName . '.cache';

        if (!$bypass && file_exists($cacheFile)) {

            $timerName = 'Cache arr HIT: ' . $cacheName;
            G::timerStart($timerName);

            $result = include $cacheFile;

        } else {

            $cacheType = $bypass ? 'BYPASS' : 'MISS';
            $timerName = 'Cache arr ' . $cacheType . ': ' . $cacheName;
            G::timerStart($timerName);

            $fCache = function() use ($f, $write, $cacheFile) {
                $r = $f();
                if ($write && is_array($r)) {
                    file_put_contents($cacheFile, '<?php return ' . var_export($r, true) . ';' . PHP_EOL);
                }
                return $r;
            };

            if ($bypass) {
                $result = $fCache();
            } else {
                $result = File::lock(
                    dir: $dirCache . 'flock',
                    fileName: $cacheName . '.lock',
                    f: $fCache,
                    fOnUnlock: function() use ($cacheFile) {
                        if (file_exists($cacheFile)) {
                            return include $cacheFile;
                        }
                        return [];
                    },
                    fOnFail: $f
                );

            }

        }

        if (!is_array($result)) {
            Flash::error('Cache arr: invalid result');
            $result = [];
        }

        G::timerStop($timerName);

        return $result;
    }


    static function stringDir(
        string   $scope,
        int      $level,
        string   $key,
        callable $f,
        bool     $bypass = null,
        bool     $write = null,
        string   $dirCache = null
    ): string {
        $dirCache ??= G::$app->dirCache;

        $result = '';
        $subdir = 'app';
        if ($scope == 'editor') $subdir = 'editor';

        $cacheName = $scope . '-' . $level . '-' . $key;

        $dir = $dirCache . trim($subdir, '/') . '/';
        if (!is_dir($dir)) mkdir($dir);

        if (is_null($write)) $write = !$bypass;
        if (!$bypass) $write = true;

        $cacheFile = $dir . $cacheName . '.cache';

        if (!$bypass && file_exists($cacheFile)) {

            $timerName = 'Cache str HIT: ' . $cacheName;
            G::timerStart($timerName);

            $result = file_get_contents($cacheFile);

        } else {

            $cacheType = $bypass ? 'BYPASS' : 'MISS';
            $timerName = 'Cache str ' . $cacheType . ': ' . $cacheName;
            G::timerStart($timerName);

            $fCache = function() use ($f, $write, $cacheFile) {
                $r = $f();
                if ($write && is_string($r)) {
                    file_put_contents($cacheFile, $r);
                }
                return $r;
            };

            if ($bypass) {
                $result = $fCache();
            } else {
                $result = File::lock(
                    dir: $dirCache . 'flock',
                    fileName: $cacheName . '.lock',
                    f: $fCache,
                    fOnUnlock: function() use ($cacheFile) {
                        if (file_exists($cacheFile)) {
                            return file_get_contents($cacheFile);
                        }
                        return '';
                    },
                    fOnFail: $f
                );
            }

        }

        if (!is_string($result)) {
            Flash::error('Cache str: invalid result');
            $result = '';
        }

        G::timerStop($timerName);

        return $result;
    }


    static function deleteDir(
        $scopes,
        $key = '*',
        string $dirCache = null
    ): void {
        $dirCache ??= G::$app->dirCache;

        $dirCacheStrlen = strlen($dirCache);
        if (!is_array($scopes)) $scopes = [$scopes];
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


    static function deleteAllDir(string $dirCache = null): void {
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


    static function deleteOldDir(string $dirCache = null): void {
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
        return AppCache::arrayDir(
            scope: 'app',
            level: 1,
            key: "route-primary-" . G::$req->minStatus,
            f: $f,
            bypass: false,
            dirCache: G::$app->dirCache
        );
    }

    static function subpage(
        callable $f,
        string   $table
    ): array {
        return AppCache::arrayDir(
            scope: 'app',
            level: 5,
            key: "route-subpage-$table-" . G::$req->minStatus,
            f: $f,
            bypass: G::$req->cacheBypass,
            write: G::$req->cacheWrite,
            dirCache: G::$app->dirCache
        );
    }

    static function subpageLang(
        callable $f,
        string   $table
    ): array {
        return AppCache::arrayDir(
            scope: 'app',
            level: 5,
            key: "route-subpageLang-$table-" . G::$req->minStatus,
            f: $f,
            bypass: G::$req->cacheBypass,
            write: G::$req->cacheWrite,
            dirCache: G::$app->dirCache
        );
    }

    static function array(
        string $level,
        string $key,
        callable $f,
        bool $bypass = null,
        bool $write = null
    ): array {
        return AppCache::arrayDir(
            scope: 'app',
            level: $level,
            key: "$key-" . G::$req->minStatus,
            f: $f,
            bypass: $bypass ?? G::$req->cacheBypass,
            write: $write ?? G::$req->cacheWrite,
            dirCache: G::$app->dirCache
        );
    }

    static function string(
        string $level,
        string $key,
        callable $f,
        bool $bypass = null,
        bool $write = null
    ): string {
        return AppCache::stringDir(
            scope: 'app',
            level: $level,
            key: "$key-" . G::$req->minStatus,
            f: $f,
            bypass: $bypass ?? G::$req->cacheBypass,
            write: $write ?? G::$req->cacheWrite,
            dirCache: G::$app->dirCache
        );
    }

    static function delete($scopes, $key = '*'): void {
        AppCache::deleteDir($scopes, $key, G::$app->dirCache);
    }

    static function deleteAll(): void {
        AppCache::deleteAllDir(G::$app->dirCache);
    }

    static function deleteOld(): void {
        AppCache::deleteOldDir(G::$app->dirCache);
    }

}
