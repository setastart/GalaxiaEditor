<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12


use Galaxia\G;
use GalaxiaEditor\E;
use GalaxiaEditor\stats\Stats;


$stats = Stats::getGoaccessDate(E::$itemDate);

$curDate = date('Y-m-d');
if ($curDate == E::$itemDate) {
    $stats = Stats::getGoaccessTemp(E::$section['gcGoaccessLog'] ?? '', E::$section['gcGoaccessDir'] ?? '');
}

if (is_null($stats)) G::errorPage(404, 'Could not load Stats file');


exit($stats);
