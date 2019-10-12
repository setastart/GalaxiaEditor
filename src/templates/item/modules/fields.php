<?php


$module['inputs'] = [];
$module['inputsUnused'] = [];


// query extras

$extras = [];
foreach ($module['gcSelectExtra'] as $table => $cols) {
    $query = querySelect([$table => $cols]);
    $query .= querySelectOrderBy([$table => [$cols[1] => 'ASC']]);
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($extraData = $result->fetch_assoc()) {
        $extraData = array_map('strval', $extraData);
        $extras[$table][] = $extraData;
    }
    $stmt->close();
}




// query fields

$where = [$module['gcTable'] => [$item['gcTable'] . 'Id' => '=']];

$query = querySelect($module['gcSelect']);
$query .= querySelectLeftJoinUsing($module['gcSelectLJoin']);
$query .= querySelectWhere($where);
$query .= querySelectOrderBy($module['gcSelectOrderBy']);

$stmt = $db->prepare($query);
$stmt->bind_param('s', $itemId);
$stmt->execute();
$result = $stmt->get_result();

$fieldsData = [];
while ($data = $result->fetch_assoc()) {
    $data = array_map('strval', $data);
    $fieldsData[$data['fieldKey']][$data[$module['gcTable'] . 'Id']] = $data;
}
$stmt->close();




// - prepare geInputs

foreach ($module['gcInputs'] as $inputKey => $input) {
    $module['gcInputs'][$inputKey] = prepareInput($input, $app->dirImages, $extras);
}




// - prepare geInputsWhereCol merging from geInputs
// - add proto and new-0 to $module['inputs'][$fieldKey']

foreach ($module['gcInputsWhereCol'] as $fieldKey => $inputs) {
    foreach ($inputs as $inputKey => $input) {
        $input = array_replace_recursive($module['gcInputs'][$inputKey], $input);
        $input = prepareInput($input, $app->dirImages, $extras);
        if (empty($input['label'])) $input['label'] = $fieldKey;
        $input['nameFromDb'] = $inputKey;

        $input['name'] = 'modules[' . $moduleKey . '][' . $fieldKey . '][proto][' . $inputKey . ']';
        $module['inputs'][$fieldKey]['proto'][$inputKey] = $input;

        $input['name'] = 'modules[' . $moduleKey . '][' . $fieldKey . '][new-0][' . $inputKey . ']';
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



// - prepare geInputsWhereParent merging from geInputs
// - add proto and new-0 to $module['inputs'][$fieldKey']

foreach ($module['gcInputsWhereParent'] as $parentName => $parent) {
    foreach ($parent as $parentValue => $inputsDefault) {

        if (!isset($item['data'][$parentName])) continue;
        if ($item['data'][$parentName] != $parentValue) continue;

        foreach ($inputsDefault as $fieldKey => $inputs) {
            foreach ($inputs as $inputKey => $input) {
                $input = array_replace_recursive($module['gcInputs'][$inputKey], $input);
                $input = prepareInput($input, $app->dirImages, $extras);
                if (empty($input['label'])) $input['label'] = $fieldKey;
                $input['nameFromDb'] = $inputKey;
                $input['value'] = '';

                $input['name'] = 'modules[' . $moduleKey . '][' . $fieldKey . '][proto][' . $inputKey . ']';
                $module['inputs'][$fieldKey]['proto'][$inputKey] = $input;

                $input['name'] = 'modules[' . $moduleKey . '][' . $fieldKey . '][new-0][' . $inputKey . ']';
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




// merge database $fieldsData data into $module['inputs']

$newFieldsToDelete = [];
foreach ($fieldsData as $fieldKey => $field) {

    foreach ($field as $fieldId => $data) {

        foreach ($module['gcInputs'] as $inputKey => $input) {
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
                $newFieldsToDelete[$fieldKey] = true;
            } else {
                // $module['inputsUnused'][$fieldKey][$fieldId][$inputKey] = array_replace($input, $module['gcInputs'][$inputKey], $inputNew);
                // $module['inputsUnused'][$fieldKey][$inputKey] = array_replace($input, $module['inputsUnused'][$fieldKey][$inputKey], $inputNew);
                // $module['inputsUnused'][$fieldKey][$inputKey]['cssClass'] = $module['gcModuleShowUnused']['cssClass'] ?? '';
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
