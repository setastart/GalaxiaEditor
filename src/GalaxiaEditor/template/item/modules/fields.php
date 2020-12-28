<?php


use Galaxia\Sql;
use GalaxiaEditor\input\Input;


$module['inputs']       = [];
$module['inputsUnused'] = [];


// query extras

$extras = [];
foreach ($module['gcSelectExtra'] as $table => $cols) {
    $query = Sql::select([$table => $cols]);
    $query .= Sql::selectOrderBy([$table => [$cols[1] => 'ASC']]);
    $stmt  = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($extraData = $result->fetch_assoc()) {
        $extraData        = array_map('strval', $extraData);
        $extras[$table][] = $extraData;
    }
    $stmt->close();
}



// query fields

$where = [$module['gcTable'] => [$item['gcTable'] . 'Id' => '=']];

$fieldCol = 'fieldKey';
if (in_array($module['gcTable'] . 'Field', $module['gcSelect'][$module['gcTable']])) {
    $fieldCol = $module['gcTable'] . 'Field';
}

$query = Sql::select($module['gcSelect']);
$query .= Sql::selectLeftJoinUsing($module['gcSelectLJoin']);
$query .= Sql::selectWhere($where);
$query .= Sql::selectOrderBy($module['gcSelectOrderBy']);

$stmt = $db->prepare($query);
$stmt->bind_param('s', $itemId);
$stmt->execute();
$result = $stmt->get_result();

$fieldsData = [];
while ($data = $result->fetch_assoc()) {
    $data = array_map('strval', $data);

    $fieldsData[$data[$fieldCol]][$data[$module['gcTable'] . 'Id']] = $data;
}
$stmt->close();




// - prepare gcInputs
foreach ($module['gcInputsWhereParent'] as $parentName => $parent) {
    foreach ($parent as $parentValue => $inputsDefault) {
        if (!isset($item['data'][$parentName])) continue;
        if ($item['data'][$parentName] != $parentValue) continue;
        foreach ($inputsDefault as $fieldKey => $inputs) {
            foreach ($inputs as $inputKey => $input) {
                if ($inputKey == 'gcMulti') continue;
                if ($inputKey == $fieldCol) continue;

                $module['inputs'][$fieldKey]['proto'][$inputKey] = [];
                $module['inputs'][$fieldKey]['new-0'][$inputKey] = [];
            }
        }
    }
}
foreach ($module['gcInputs'] as $inputKey => $input) {
    $module['gcInputs'][$inputKey] = Input::prepare($input, $extras);
}




// - prepare gcInputsWhereCol merging from gcInputs
// - add proto and new-0 to $module['inputs'][$fieldKey']

foreach ($module['gcInputsWhereCol'] as $fieldKey => $inputs) {
    foreach ($inputs as $inputKey => $inputOriginal) {
        if ($inputKey == 'gcMulti') {
            $module['gcModuleMultiple'][$fieldKey] = $module['gcModuleMultiple'][$fieldKey] ?? $inputOriginal;
            continue;
        }

        $input = array_replace_recursive($module['gcInputs'][$inputKey], $inputOriginal);
        $input = Input::prepare($input, $extras);

        if (isset($module['gcModuleMultiple'][$fieldKey])) {
            $input['label'] = $inputOriginal['label'] ?? $module['gcInputs'][$inputKey]['label'] ?? $geConf[$pgSlug]['gcColNames'][$inputKey] ?? $inputKey;
        } else {
            $input['label'] = $inputOriginal['label'] ?? $module['gcInputs'][$inputKey]['label'] ?? $geConf[$pgSlug]['gcColNames'][$inputKey] ?? $fieldKey ?? $inputKey;
        }

        $input['nameFromDb'] = $inputKey;
        $input['name']       = 'modules[' . $moduleKey . '][' . $fieldKey . '][new-0][' . $inputKey . ']';

        $module['inputs'][$fieldKey]['proto'][$inputKey] = $input;
        $module['inputs'][$fieldKey]['new-0'][$inputKey] = $input;
    }

    if (isset($module['gcModuleMultiple'][$fieldKey])) {
        $module['inputs'][$fieldKey]['proto']['delete'] = '';
        $module['inputs'][$fieldKey]['new-0']['delete'] = '';
        if ($module['gcModuleMultiple'][$fieldKey]['reorder']) {
            $module['inputs'][$fieldKey]['proto']['position'] = 1;
            $module['inputs'][$fieldKey]['new-0']['position'] = 1;
        }
    }
}




// - prepare gcInputsWhereParent merging from gcInputs
// - add proto and new-0 to $module['inputs'][$fieldKey']

foreach ($module['gcInputsWhereParent'] as $parentName => $parent) {
    foreach ($parent as $parentValue => $inputsDefault) {
        if (!isset($item['data'][$parentName])) continue;
        if ($item['data'][$parentName] != $parentValue) continue;

        foreach ($inputsDefault as $fieldKey => $inputs) {
            foreach ($inputs as $inputKey => $inputOriginal) {
                // if ($inputOriginal['type'] == 'none') {
                //     unset($module['inputs'][$fieldKey]);
                //     continue 2;
                // }
                if ($inputKey == 'gcMulti') {
                    $module['gcModuleMultiple'][$fieldKey] = $module['gcModuleMultiple'][$fieldKey] ?? $inputOriginal;
                    continue;
                }

                $input = array_replace_recursive($module['gcInputsWhereCol'][$fieldKey][$inputKey] ?? $module['gcInputs'][$inputKey], $inputOriginal);
                $input = Input::prepare($input, $extras);

                $input['label']      = $inputOriginal['label'] ?? $module['gcInputsWhereCol'][$fieldKey][$inputKey]['label'] ?? $module['gcInputs'][$inputKey]['label'] ?? $geConf[$pgSlug]['gcColNames'][$inputKey] ?? $fieldKey ?? $inputKey;
                $input['nameFromDb'] = $inputKey;
                $input['value']      = '';
                $input['name']       = 'modules[' . $moduleKey . '][' . $fieldKey . '][new-0][' . $inputKey . ']';

                $module['inputs'][$fieldKey]['proto'][$inputKey] = $input;
                $module['inputs'][$fieldKey]['new-0'][$inputKey] = $input;
            }
            if (isset($module['gcModuleMultiple'][$fieldKey])) {
                $module['inputs'][$fieldKey]['proto']['delete'] = '';
                $module['inputs'][$fieldKey]['new-0']['delete'] = '';
                if ($module['gcModuleMultiple'][$fieldKey]['reorder']) {
                    $module['inputs'][$fieldKey]['proto']['position'] = 1;
                    $module['inputs'][$fieldKey]['new-0']['position'] = 1;
                }
            }
        }
    }
}



// remove inputs that are disabled by setting type to none
foreach ($module['inputs'] as $fieldKey => $fields) {
    foreach ($fields as $fieldVal => $field) {
        foreach ($field as $inputKey => $input) {
            if (($input['type'] ?? '') == 'none') unset($module['inputs'][$fieldKey]);
        }
    }
}




// merge database $fieldsData data into $module['inputs']

$newFieldsToDelete = [];
foreach ($fieldsData as $fieldKey => $field) {

    foreach ($field as $fieldId => $data) {

        foreach ($module['gcInputs'] as $inputKey => $input) {
            if ($inputKey == 'gcMulti') {
                $module['inputs'][$fieldKey][$fieldId]['delete'] = '';
                if ($input['reorder']) {
                    $module['inputs'][$fieldKey][$fieldId]['position'] = $data['position'] ?? 1;
                }
                continue;
            }

            $value = $data[$inputKey];
            // if (isset($input['nullable']) && $input['nullable'] && !$value) $value = null;
            if (substr($inputKey, 0, 9) == 'timestamp') $value = date('Y-m-d H:i:s', $data[$inputKey]);
            $inputNew = [
                'name'        => 'modules[' . $moduleKey . '][' . $fieldKey . '][' . $fieldId . '][' . $inputKey . ']',
                'nameFromDb'  => $inputKey,
                'value'       => $value,
                'valueFromDb' => $value,
            ];

            if (isset($module['inputs'][$fieldKey]['proto'][$inputKey])) {
                $module['inputs'][$fieldKey][$fieldId][$inputKey] = array_replace($input, $module['inputs'][$fieldKey]['proto'][$inputKey], $inputNew);
                $newFieldsToDelete[$fieldKey]                     = true;
            } else {
                // $module['inputsUnused'][$fieldKey][$fieldId][$inputKey] = array_replace($input, $module['gcInputs'][$inputKey], $inputNew);
                // $module['inputsUnused'][$fieldKey][$fieldId][$inputKey] = array_replace($input, $module['inputsUnused'][$fieldKey][$inputKey], $inputNew);
                // $module['inputsUnused'][$fieldKey][$fieldId][$inputKey]['cssClass'] = $module['gcModuleShowUnused']['cssClass'] ?? '';
            }

            if (isset($input['lang']) && count($app->langs) > 1) $showSwitchesLang = true;
        }

        if (isset($module['gcModuleMultiple'][$fieldKey])) {
            $module['inputs'][$fieldKey][$fieldId]['delete'] = '';
            if ($module['gcModuleMultiple'][$fieldKey]['reorder']) {
                $module['inputs'][$fieldKey][$fieldId]['position'] = $data['position'] ?? 1;
            }
        }

    }
}

foreach ($newFieldsToDelete as $fieldKey => $field) {
    if (!isset($module['gcModuleMultiple'][$fieldKey])) {
        unset($module['inputs'][$fieldKey]['new-0']);
    }
}



// reorder fields

if ($module['gcFieldOrder'] ?? false) {
    $order = array_flip($module['gcFieldOrder']);
    uksort($module['inputs'], function($a, $b) use ($order, $module) {
        $aSearch = $order[$a] ?? $order['gcDefault'] ?? -1;
        $bSearch = $order[$b] ?? $order['gcDefault'] ?? -1;

        if ($aSearch === $bSearch) {
            $aSearch = array_search($a, array_keys($module['inputs']));
            $bSearch = array_search($b, array_keys($module['inputs']));
        };

        return $aSearch <=> $bSearch;
    });
}
