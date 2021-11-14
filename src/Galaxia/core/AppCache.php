<?php


namespace Galaxia;


class AppCache {

    static function get(
        string $dirCache, string $scope, int $level, string $key,
        callable $f, bool $bypass = null, bool $write = null
    ): array {

        $subdir = 'app';
        if ($scope == 'editor') $subdir = 'editor';

        $cacheName = $scope . '-' . $level . '-' . $key;

        $dir = $dirCache . trim($subdir, '/') . '/';
        if (!is_dir($dir)) mkdir($dir);

        if (is_null($bypass)) $bypass = (G::getApp()->cacheBypass == true);
        if (is_null($write)) $write = !$bypass;
        if (!$bypass) $write = true;

        $cacheFile = $dir . $cacheName . '.cache';

        if (!$bypass && file_exists($cacheFile)) {

            $timerName = 'Cache HIT: ' . $cacheName;
            G::timerStart($timerName);

            /** @noinspection PhpIncludeInspection */
            $result = include $cacheFile;

        } else {

            $result    = null;
            $cacheType = $bypass ? 'BYPASS' : 'MISS';
            $timerName = 'Cache ' . $cacheType . ': ' . $cacheName;
            G::timerStart($timerName);

            $fImageWrite = function() use ($f, $write, $cacheFile) {
                $r = $f();
                if ($write && is_array($r)) {
                    file_put_contents($cacheFile, '<?php return ' . var_export($r, true) . ';' . PHP_EOL);
                }

                return $r;
            };

            if ($bypass) {
                $result = $fImageWrite();
            } else {
                $result = File::lock($dirCache . 'flock', $cacheName . '.lock', $fImageWrite);
            }

        }

        if (!is_array($result)) {
            Flash::error('Cache: invalid result');
        }

        G::timerStop($timerName);

        return $result ?? [];
    }


    static function delete(string $dirCache, $scopes, $key = '*') {
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


    static function deleteAll(string $dirCache) {
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


    static function deleteOld(string $dirCache) {
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

}
