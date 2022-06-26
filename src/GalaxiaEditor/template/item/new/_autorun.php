<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\G;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


E::$pgTitle = Text::t('+ Add') . ' ' . Text::t(E::$section['gcTitleSingle']);
E::$hdTitle = Text::t('+ Add') . ' ' . Text::t(E::$section['gcTitleSingle']);


// query extras

$extras = [];
foreach (E::$item['gcSelectExtra'] as $table => $cols) {
    $query = Sql::select([$table => $cols]);
    $query .= Sql::selectOrderBy([$table => [$cols[1] => 'ASC']]);

    $stmt = G::prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($extraData = $result->fetch_assoc()) {
        $extraData = array_map('strval', $extraData);
        $extras[$table][] = $extraData;
    }
    $stmt->close();
}




foreach (E::$item['gcInputs'] as $inputKey => $input) {
    $input = Input::prepare($input, $extras);

    E::$item['inputs'][$inputKey] = array_merge($input, [
        'label'       => $input['label'] ?? E::$section['gcColNames'][$inputKey] ?? $inputKey,
        'name'        => 'item[' . $inputKey . ']',
        'nameFromDb'  => $inputKey,
    ]);
    if ($input['type'] == 'timestamp') E::$item['inputs'][$inputKey]['value'] = date('Y-m-d 00:00');
    if ($input['type'] == 'datetime')  E::$item['inputs'][$inputKey]['value'] = date('Y-m-d 00:00');

    if (isset($input['lang'])) E::$showSwitchesLang = true;
}
