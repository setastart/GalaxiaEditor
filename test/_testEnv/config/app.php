<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12


use Galaxia\G;

// Session::$redis = true;
// Session::$debug = true;
// User::$redis = true;
// User::$debug = true;

G::$app->locales  = [
    'en' => ['url' => '/', 'long' => 'en_US', 'full' => 'English'],
    'es' => ['url' => '/es', 'long' => 'es_ES', 'full' => 'Castellano'],
    'pt' => ['url' => '/pt', 'long' => 'pt_PT', 'full' => 'Português'],
];
G::$app->lang     = 'es';
G::$app->timeZone = 'Europe/Madrid';
