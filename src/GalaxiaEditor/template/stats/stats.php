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


use GalaxiaEditor\stats\Stats;


$statFiles = Stats::getStats(1);

$stats = [];
$statsYMD = [];
foreach ($statFiles as $path) {
    $fileName = pathinfo($path, PATHINFO_FILENAME);
    $date = substr($fileName, 0, 10);
    $day = include 'compress.zlib://' . $path;
    $stats[] = $day;
}

// $stats = array_merge_recursive(...$stats);
Kint::$max_depth = 0;
dd($stats);
