<?php


foreach ($postModule as $fieldKey => $fields) {

    if (!isset($modules[$moduleKey]['inputs'][$fieldKey])) {
        error('Invalid input field name: ' . h($fieldKey));
        continue;
    }

    if (isset($modules[$moduleKey]['gcModuleMultiple'][$fieldKey])) {
        foreach ($fields as $fieldVal => $field) {
            if ($fieldVal == 'new-0') unset($fields[$fieldVal]);
        }

        // sort fields so new fields come last, so that unique constraint errors appear on newer items
        uksort($fields, function($a, $b) {
            if (is_numeric($b) && !is_numeric($a)) return 1;
            else if (!is_numeric($b) && is_numeric($a)) return -1;
        });
    }


    foreach ($fields as $fieldVal => $field) {

        // new fields
        if (is_string($fieldVal)) {
            if (substr($fieldVal, 0, 4) != 'new-') {
                error('Invalid new input field name: ' . h($fieldKey) . '/' . h(fieldVal));
                continue;
            }
            $newId = substr($fieldVal, 4);
            if (!ctype_digit($newId)) {
                error('Invalid new input field id: ' . h($fieldKey) . '/' . h(fieldVal));
                continue;
            }
            $newId = (int)$newId;

            if (!isset($modules[$moduleKey]['inputs'][$fieldKey]['new-0'])) {
                error('Missing new input field id in module: ' . h($fieldKey) . '/new-0');
                continue;
            }

            if (isset($field['delete']) && $field['delete'] == 'on') continue;

            foreach ($field as $name => $val) {
                if ($name == 'delete') {
                    if (in_array($val, ['', 'on']))
                        $modules[$moduleKey]['inputs'][$fieldKey]['new-' . $newId]['delete'] = $val;
                    else
                        error('Invalid new input field delete: ' . h($fieldKey) . '/' . h(fieldVal) . '/' . h($val));
                    continue;
                }
                if ($name == 'position') {
                    if (ctype_digit($val)) {
                        $modules[$moduleKey]['inputs'][$fieldKey]['new-' . $newId]['position'] = $val;
                        $fieldsNew[$moduleKey][$fieldKey][$fieldVal][$name] = $val;
                    } else {
                        error('Invalid new input field position: ' . h($fieldKey) . '/' . h(fieldVal) . '/' . h($val));
                    }
                    continue;
                }

                $input = $modules[$moduleKey]['inputs'][$fieldKey]['new-0'][$name];
                $input['name'] = 'modules[' . $moduleKey . '][' . $fieldKey . '][' . $fieldVal . '][' . $name . ']';
                $input = validateInput($input, $val);

                $modules[$moduleKey]['inputs'][$fieldKey]['new-' . $newId][$name] = $input;

                if ($input['nullable'] && !$input['value'] && !$input['valueFromDb']) $input['valueFromDb'] = null;

                if ($input['value'] !== $input['valueFromDb'])
                    $fieldsNew[$moduleKey][$fieldKey][$fieldVal][$name] = $input['valueToDb'];
            }
            continue;
        }

        if (!is_int($fieldVal)) {
            error('Invalid input field id - not numeric: ' . h($fieldKey) . '/' . h(fieldVal));
            continue;
        }

        // deleted fields
        if (isset($field['delete']) && $field['delete'] == 'on') {
            $fieldsDel[$moduleKey][$fieldKey][] = $fieldVal;
            $modules[$moduleKey]['inputs'][$fieldKey][$fieldVal]['delete'] = 'on';
            continue;
        }

        // updated fields
        foreach ($field as $name => $val) {
            if ($name == 'delete') {
                if (in_array($val, ['', 'on']))
                    $modules[$moduleKey]['inputs'][$fieldKey][$fieldVal]['delete'] = $val;
                else
                    error('Invalid new input field delete: ' . h($fieldKey) . '/' . h(fieldVal) . '/' . h($val));
                continue;
            }
            if ($name == 'position') {
                if (ctype_digit($val)) {
                    if ($modules[$moduleKey]['inputs'][$fieldKey][$fieldVal]['position'] != $val) {
                        $modules[$moduleKey]['inputs'][$fieldKey][$fieldVal]['position'] = $val;
                        $fieldsUpd[$moduleKey][$fieldKey][$fieldVal][$name] = $val;
                    }
                } else {
                    error('Invalid new input field position: ' . h($fieldKey) . '/' . h(fieldVal) . '/' . h($val));
                }
                continue;
            }

            $input = validateInput($modules[$moduleKey]['inputs'][$fieldKey][$fieldVal][$name], $val);

            $modules[$moduleKey]['inputs'][$fieldKey][$fieldVal][$name] = $input;

            if ($input['nullable'] && !$input['value'] && !$input['valueFromDb']) $input['valueFromDb'] = null;

            if ($input['value'] !== $input['valueFromDb'])
                $fieldsUpd[$moduleKey][$fieldKey][$fieldVal][$name] = $input['valueToDb'];

        }

    }

    // reorder fields
    if (isset($modules[$moduleKey]['gcModuleMultiple'][$fieldKey])) {
        if ($modules[$moduleKey]['gcModuleMultiple'][$fieldKey]['reorder']) {
            uasort($modules[$moduleKey]['inputs'][$fieldKey], function($a, $b) {
                return $a['position'] <=> $b['position'];
            });
        } else {
            $orderBy = array_reverse($modules[$moduleKey]['gcSelectOrderBy']);
            foreach ($orderBy as $tabKey => $cols) {
                foreach ($cols as $colKey => $colSort) {
                    if (!isset($modules[$moduleKey]['inputs'][$fieldKey][$colKey])) continue;
                    if ($colSort == 'ASC') {
                        uasort($modules[$moduleKey]['inputs'][$fieldKey], function($a, $b) use ($colKey) {
                            return $a[$colKey]['value'] <=> $b[$colKey]['value'];
                        });
                        uksort($modules[$moduleKey]['inputs'][$fieldKey], function($a, $b) {
                            if (is_numeric($a) && !is_numeric($b)) return 1;
                            else if (!is_numeric($a) && is_numeric($b)) return -1;
                        });
                    } else {
                        uasort($modules[$moduleKey]['inputs'][$fieldKey], function($a, $b) use ($colKey) {
                            return $b[$colKey]['value'] <=> $a[$colKey]['value'];
                        });
                        uksort($modules[$moduleKey]['inputs'][$fieldKey], function($a, $b) {
                            if (is_numeric($b) && !is_numeric($a)) return 1;
                            else if (!is_numeric($b) && is_numeric($a)) return -1;
                        });
                    }
                }
            }
        }
    }

    // error on duplicate unique input
    if (isset($modules[$moduleKey]['gcModuleMultiple'][$fieldKey])) {
        if ($modules[$moduleKey]['gcModuleMultiple'][$fieldKey]['unique']) {
            $visited = [];
            foreach ($fields as $fieldVal => $field) {
                if (!isset($modules[$moduleKey]['inputs'][$fieldKey][$fieldVal])) continue;
                if (isset($field['delete']) && $field['delete'] == 'on') continue;
                foreach ($modules[$moduleKey]['gcModuleMultiple'][$fieldKey]['unique'] as $unique) {
                    $val = $modules[$moduleKey]['inputs'][$fieldKey][$fieldVal][$unique]['value'];
                    $visited[$fieldVal][$unique] = $val;
                }
            }

            $msg = t('Must be unique. An item with that value already exists.');
            foreach ($visited as $fieldVal => $current_array) {
                $fieldValSearch = array_search($current_array, $visited);
                if ($fieldVal != $fieldValSearch) {
                    foreach ($current_array as $unique => $values) {
                        error($msg, 'form', $modules[$moduleKey]['inputs'][$fieldKey][$fieldVal][$unique]['name']);
                        $modules[$moduleKey]['inputs'][$fieldKey][$fieldVal][$unique]['errors'][] = $msg;
                        if ($modules[$moduleKey]['inputs'][$fieldKey][$fieldVal][$unique]['lang']) {
                            $langSelectClass[$modules[$moduleKey]['inputs'][$fieldKey][$fieldVal][$unique]['lang']] = 'btn-red';
                        }
                    }
                }
            }
        }
    }

}






return;

foreach ($postModule as $fieldName => $fieldValue) {
    foreach ($fieldValue as $name => $value) {
        if (isset($modules[$moduleKey]['inputs'][$fieldName][$name])) {
            $input = validateInput($modules[$moduleKey]['inputs'][$fieldName][$name], $value);

            $modules[$moduleKey]['inputs'][$fieldName][$name] = $input;
        } else if (isset($modules[$moduleKey]['inputsUnused'][$fieldName][$name])) {
            $input = validateInput($modules[$moduleKey]['inputsUnused'][$fieldName][$name], $value);

            $modules[$moduleKey]['inputsUnused'][$fieldName][$name] = $input;
        } else {
            $input['errors'][] = 'Non existant input.';
        }

        if (empty($input['errors']) && $input['dbUnique']) {
            $query = querySelectFirst($module['gcSelect']);
            $query .= querySelectWhere([$input['nameFromDb'] => '=']);
            $query .= querySelectLimit(0, 1);

            $stmt = $db->prepare($query);
            $stmt->bind_param('s', $input['value']);
            $stmt->bind_result($rowId);
            $stmt->execute();
            $stmt->fetch();
            $stmt->close();

            if ($rowId && (string)$rowId != $input['value']) {
                $input['errors'][] = 'Must be unique. An item with that value already exists.';
            }

        }

        foreach ($input['errors'] as $msg) {
            error($msg, 'form', $input['name']);
            if ($input['lang']) {
                $langSelectClass[$input['lang']] = 'btn-red';
            }
        }


        $nameFromDb = $input['nameFromDb'];
        $valueToDb =  $input['valueToDb'];

        if ($input['value'] !== $input['valueFromDb']) {
            $modules[$moduleKey]['inputs'][$fieldName][$name] = $input;
        }

    }
}

