<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12


use Galaxia\G;
use Test\GalaxiaTest;

include_once dirname(__DIR__, 2) . '/src/boot-cli-editor.php';

$rebuildDbQuery = file_get_contents(__DIR__ . '/testDb.sql');

$mysqli = new mysqli('', 'root', '', '');
if ($mysqli->connect_errno) {
    GalaxiaTest::error('G db Connection Failed' . __METHOD__ . ':' . __LINE__ . ' ' . $mysqli->connect_errno);
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli->set_charset('utf8mb4');
$mysqli->multi_query($rebuildDbQuery);
while ($mysqli->next_result()) { echo ''; }
$mysqli->close();
