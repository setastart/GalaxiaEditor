<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\G;
use Galaxia\Sql;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


E::$module['inputs']       = [];
E::$module['inputsUnused'] = [];


// query extras

$extras = [];
foreach (E::$module['gcSelectExtra'] as $table => $cols) {
    $query = Sql::select([$table => $cols]);
    $query .= Sql::selectOrderBy([$table => [$cols[1] => 'ASC']]);
    $stmt  = G::prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($extraData = $result->fetch_assoc()) {
        $extraData        = array_map('strval', $extraData);
        $extras[$table][] = $extraData;
    }
    $stmt->close();
}



// query fields

$where = [E::$module['gcTable'] => [E::$item['gcTable'] . 'Id' => '=']];

$fieldCol = 'fieldKey';
if (in_array(E::$module['gcTable'] . 'Field', E::$module['gcSelect'][E::$module['gcTable']])) {
    $fieldCol = E::$module['gcTable'] . 'Field';
}

$query = Sql::select(E::$module['gcSelect']);
$query .= Sql::selectLeftJoinUsing(E::$module['gcSelectLJoin']);
$query .= Sql::selectWhere($where);
$query .= Sql::selectOrderBy(E::$module['gcSelectOrderBy']);

$stmt = G::prepare($query);
$stmt->bind_param('s', E::$itemId);
$stmt->execute();
$result = $stmt->get_result();

$fieldsData = [];
while ($data = $result->fetch_assoc()) {
    $data = array_map('strval', $data);

    $fieldsData[$data[$fieldCol]][$data[E::$module['gcTable'] . 'Id']] = $data;
}
$stmt->close();




// - prepare gcInputs
foreach (E::$module['gcInputsWhereParent'] as $parentName => $parent) {
    foreach ($parent as $parentValue => $inputsDefault) {
        if (!isset(E::$item['data'][$parentName])) continue;
        if (E::$item['data'][$parentName] != $parentValue) continue;
        foreach ($inputsDefault as $fieldKey => $inputs) {
            foreach ($inputs as $inputKey => $input) {
                if ($inputKey == 'gcMulti') continue;
                if ($inputKey == $fieldCol) continue;

                E::$module['inputs'][$fieldKey]['proto'][$inputKey] = [];
                E::$module['inputs'][$fieldKey]['new-0'][$inputKey] = [];
            }
        }
    }
}
foreach (E::$module['gcInputs'] as $inputKey => $input) {
    E::$module['gcInputs'][$inputKey] = Input::prepare($input, $extras);
}




// - prepare gcInputsWhereCol merging from gcInputs
// - add proto and new-0 to E::$module['inputs'][$fieldKey']

foreach (E::$module['gcInputsWhereCol'] as $fieldKey => $inputs) {
    foreach ($inputs as $inputKey => $inputOriginal) {
        if ($inputKey == 'gcMulti') {
            E::$module['gcModuleMultiple'][$fieldKey] = E::$module['gcModuleMultiple'][$fieldKey] ?? $inputOriginal;
            continue;
        }

        $input = array_replace_recursive(E::$module['gcInputs'][$inputKey], $inputOriginal);
        $input = Input::prepare($input, $extras);

        if (isset(E::$module['gcModuleMultiple'][$fieldKey])) {
            $input['label'] = $inputOriginal['label'] ?? E::$module['gcInputs'][$inputKey]['label'] ?? E::$section['gcColNames'][$inputKey] ?? $inputKey;
        } else {
            $input['label'] = $inputOriginal['label'] ?? E::$module['gcInputs'][$inputKey]['label'] ?? E::$section['gcColNames'][$inputKey] ?? $fieldKey ?? $inputKey;
        }

        $input['nameFromDb'] = $inputKey;
        $input['name']       = 'modules[' . E::$moduleKey . '][' . $fieldKey . '][new-0][' . $inputKey . ']';

        E::$module['inputs'][$fieldKey]['proto'][$inputKey] = $input;
        E::$module['inputs'][$fieldKey]['new-0'][$inputKey] = $input;
    }

    if (isset(E::$module['gcModuleMultiple'][$fieldKey])) {
        E::$module['inputs'][$fieldKey]['proto']['delete'] = '';
        E::$module['inputs'][$fieldKey]['new-0']['delete'] = '';
        if (E::$module['gcModuleMultiple'][$fieldKey]['reorder']) {
            E::$module['inputs'][$fieldKey]['proto']['position'] = 1;
            E::$module['inputs'][$fieldKey]['new-0']['position'] = 1;
        }
    }
}




// - prepare gcInputsWhereParent merging from gcInputs
// - add proto and new-0 to E::$module['inputs'][$fieldKey']

foreach (E::$module['gcInputsWhereParent'] as $parentName => $parent) {
    foreach ($parent as $parentValue => $inputsDefault) {
        if (!isset(E::$item['data'][$parentName])) continue;
        if (E::$item['data'][$parentName] != $parentValue) continue;

        foreach ($inputsDefault as $fieldKey => $inputs) {
            foreach ($inputs as $inputKey => $inputOriginal) {
                // if ($inputOriginal['type'] == 'none') {
                //     unset(E::$module['inputs'][$fieldKey]);
                //     continue 2;
                // }
                if ($inputKey == 'gcMulti') {
                    E::$module['gcModuleMultiple'][$fieldKey] = E::$module['gcModuleMultiple'][$fieldKey] ?? $inputOriginal;
                    continue;
                }

                $input = array_replace_recursive(E::$module['gcInputsWhereCol'][$fieldKey][$inputKey] ?? E::$module['gcInputs'][$inputKey], $inputOriginal);
                $input = Input::prepare($input, $extras);

                $input['label']      = $inputOriginal['label'] ?? E::$module['gcInputsWhereCol'][$fieldKey][$inputKey]['label'] ?? E::$module['gcInputs'][$inputKey]['label'] ?? E::$section['gcColNames'][$inputKey] ?? $fieldKey ?? $inputKey;
                $input['nameFromDb'] = $inputKey;
                $input['value']      = '';
                $input['name']       = 'modules[' . E::$moduleKey . '][' . $fieldKey . '][new-0][' . $inputKey . ']';

                E::$module['inputs'][$fieldKey]['proto'][$inputKey] = $input;
                E::$module['inputs'][$fieldKey]['new-0'][$inputKey] = $input;
            }
            if (isset(E::$module['gcModuleMultiple'][$fieldKey])) {
                E::$module['inputs'][$fieldKey]['proto']['delete'] = '';
                E::$module['inputs'][$fieldKey]['new-0']['delete'] = '';
                if (E::$module['gcModuleMultiple'][$fieldKey]['reorder']) {
                    E::$module['inputs'][$fieldKey]['proto']['position'] = 1;
                    E::$module['inputs'][$fieldKey]['new-0']['position'] = 1;
                }
            }
        }
    }
}



// remove inputs that are disabled by setting type to none
foreach (E::$module['inputs'] as $fieldKey => $fields) {
    foreach ($fields as $fieldVal => $field) {
        foreach ($field as $inputKey => $input) {
            if (($input['type'] ?? '') == 'none') unset(E::$module['inputs'][$fieldKey]);
        }
    }
}




// merge database $fieldsData data into E::$module['inputs']

$newFieldsToDelete = [];
foreach ($fieldsData as $fieldKey => $field) {

    foreach ($field as $fieldId => $data) {

        foreach (E::$module['gcInputs'] as $inputKey => $input) {
            if ($inputKey == 'gcMulti') {
                E::$module['inputs'][$fieldKey][$fieldId]['delete'] = '';
                if ($input['reorder']) {
                    E::$module['inputs'][$fieldKey][$fieldId]['position'] = $data['position'] ?? 1;
                }
                continue;
            }

            $value = $data[$inputKey];
            // if (isset($input['nullable']) && $input['nullable'] && !$value) $value = null;
            if (str_starts_with($inputKey, 'timestamp')) $value = date('Y-m-d H:i:s', $data[$inputKey]);
            $inputNew = [
                'name'        => 'modules[' . E::$moduleKey . '][' . $fieldKey . '][' . $fieldId . '][' . $inputKey . ']',
                'nameFromDb'  => $inputKey,
                'value'       => $value,
                'valueFromDb' => $value,
            ];

            if (isset(E::$module['inputs'][$fieldKey]['proto'][$inputKey])) {
                E::$module['inputs'][$fieldKey][$fieldId][$inputKey] = array_replace($input, E::$module['inputs'][$fieldKey]['proto'][$inputKey], $inputNew);
                $newFieldsToDelete[$fieldKey]                     = true;
            // } else {
            //     E::$module['inputsUnused'][$fieldKey][$fieldId][$inputKey] = array_replace($input, E::$module['gcInputs'][$inputKey], $inputNew);
            //     E::$module['inputsUnused'][$fieldKey][$fieldId][$inputKey] = array_replace($input, E::$module['inputsUnused'][$fieldKey][$inputKey], $inputNew);
            //     E::$module['inputsUnused'][$fieldKey][$fieldId][$inputKey]['cssClass'] = E::$module['gcModuleShowUnused']['cssClass'] ?? '';
            }

            if (isset($input['lang']) && count(G::$app->langs) > 1) E::$showSwitchesLang = true;
        }

        if (isset(E::$module['gcModuleMultiple'][$fieldKey])) {
            E::$module['inputs'][$fieldKey][$fieldId]['delete'] = '';
            if (E::$module['gcModuleMultiple'][$fieldKey]['reorder']) {
                E::$module['inputs'][$fieldKey][$fieldId]['position'] = $data['position'] ?? 1;
            }
        }

    }
}

foreach ($newFieldsToDelete as $fieldKey => $field) {
    if (!isset(E::$module['gcModuleMultiple'][$fieldKey])) {
        unset(E::$module['inputs'][$fieldKey]['new-0']);
    }
}



// reorder fields

if (E::$module['gcFieldOrder'] ?? false) {
    $order = array_flip(E::$module['gcFieldOrder']);
    uksort(E::$module['inputs'], function($a, $b) use ($order) {
        $aSearch = $order[$a] ?? $order['gcDefault'] ?? -1;
        $bSearch = $order[$b] ?? $order['gcDefault'] ?? -1;

        if ($aSearch === $bSearch) {
            $aSearch = array_search($a, array_keys(E::$module['inputs']));
            $bSearch = array_search($b, array_keys(E::$module['inputs']));
        }

        return $aSearch <=> $bSearch;
    });
}
