<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace GalaxiaEditor\stats;


use Galaxia\G;
use Galaxia\Text;


class Stats {

    static function getStats(int $trim = 0): array {
        $files = glob(G::dir() . 'var/stats/????-??-??.stats.php.gz');
        arsort($files);

        if ($trim > 0) $files = array_slice($files, 0, $trim);


        return $files;
    }

    static function getGoaccessStats(int $trim = 0): array {
        $files = glob(G::dir() . 'var/goaccess/????-??-??.html');
        arsort($files);

        if ($trim > 0) $files = array_slice($files, 0, $trim);

        return $files;
    }

    static function getGoaccessDate(string $date): ?string {
        $file = G::dir() . 'var/goaccess/' . Text::h($date) . '.html';

        if (!file_exists($file)) return null;

        $stats = file_get_contents($file);
        if (!$stats) return null;

        return $stats;
    }

    static function getGoaccessTemp(string $log, string $dir): ?string {
        if (!file_exists($log)) return null;

        if (!is_dir($dir)) return null;
        $dir = rtrim($dir, '/') . '/';

        $temp = $dir . 'temp.html';

        shell_exec('goaccess ' . escapeshellarg($log) . ' --output ' . escapeshellarg($dir) . 'temp.html');


        if (!file_exists($temp)) return null;

        $stats = file_get_contents($temp);
        if (!$stats) return null;

        return $stats;
    }

}
