<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

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
    if (isset(E::$item['gcSelectExtraOrder'])) {
        $query .= Sql::selectOrderBy(E::$item['gcSelectExtraOrder']);
    } else {
        $query .= Sql::selectOrderBy([$table => [$cols[1] => 'ASC']]);
    }
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
