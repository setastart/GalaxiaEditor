<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12


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
dd($stats);
