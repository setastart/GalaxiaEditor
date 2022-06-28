<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12


use GalaxiaEditor\E;
use GalaxiaEditor\stats\Stats;


$statFiles = Stats::getGoaccessStats();

E::$statsGoaccess = [];

foreach ($statFiles as $path) {
    $fileName = pathinfo($path, PATHINFO_FILENAME);
    $date = substr($fileName, 0, 10);
    E::$statsGoaccess[$date] = true;
}
E::$statsGoaccess = array_keys(E::$statsGoaccess);
