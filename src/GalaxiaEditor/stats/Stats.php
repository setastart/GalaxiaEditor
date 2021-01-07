<?php
/*
 Copyright 2017-2021 Ino Detelić & Zaloa G. Ramos

  - Licensed under the EUPL, Version 1.2 only (the "Licence");
  - You may not use this work except in compliance with the Licence.

  - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

  - Unless required by applicable law or agreed to in writing, software distributed
    under the Licence is distributed on an "AS IS" basis,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  - See the Licence for the specific language governing permissions and limitations under the Licence.
 */

namespace GalaxiaEditor\stats;


use Galaxia\Director;
use Galaxia\Text;


class Stats {

    static function getStats(int $trim = 0) {
        $app = Director::getApp();

        $files = glob($app->dir . 'var/stats/????-??-??.stats.php.gz');
        arsort($files);

        if ($trim > 0) $files = array_slice($files, 0, $trim);


        return $files;
    }

    static function getGoaccessStats(int $trim = 0) {
        $app = Director::getApp();

        $files = glob($app->dir . 'var/goaccess/????-??-??.html');
        arsort($files);

        if ($trim > 0) $files = array_slice($files, 0, $trim);

        return $files;
    }

    static function getGoaccessDate(string $date): ?string {
        $app  = Director::getApp();
        $file = $app->dir . 'var/goaccess/' . Text::h($date) . '.html';

        if (!file_exists($file)) return null;

        $stats = file_get_contents($file);
        if (!$stats) return null;

        return $stats;
    }

}
