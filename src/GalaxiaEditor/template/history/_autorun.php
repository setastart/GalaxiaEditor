<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace GalaxiaEditor\history;

use Galaxia\G;
use Galaxia\Sql;
use GalaxiaEditor\E;


foreach (E::$conf as $rootSlug => $confPage) {
    if (!isset($confPage['gcItem'])) continue;
    if (!isset($confPage['gcItem']['gcTable'])) continue;
    E::$historyPageNames[$confPage['gcItem']['gcTable']] ??= $confPage['gcMenuTitle'];
    E::$historyInputKeys[$confPage['gcItem']['gcTable']] ??= $confPage['gcColNames'];
    E::$historyRootSlugs[$confPage['gcItem']['gcTable']] ??= $rootSlug;

    if (!isset($confPage['gcItem']['gcInputs'])) continue;
    if (!isset($confPage['gcItem']['gcInputs']['status'])) continue;
    if (!isset($confPage['gcItem']['gcInputs']['status']['options'])) continue;
    foreach ($confPage['gcItem']['gcInputs']['status']['options'] as $optionId => $option) {
        E::$historyStatusNames[$confPage['gcItem']['gcTable']][$optionId] = $option['label'];
    }
}



$query = Sql::select(['_geUser' => ['_geUserId', 'name']]);

$stmt = G::prepare($query);
$stmt->execute();
$result = $stmt->get_result();

while ($data = $result->fetch_assoc()) {
    E::$historyUserNames[$data['_geUserId']] = $data['name'];
}
$stmt->close();
