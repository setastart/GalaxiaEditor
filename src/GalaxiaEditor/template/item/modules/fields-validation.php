<?php


use Galaxia\Flash;
use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


foreach ($postModule as $fieldKey => $fields) {

    if (!isset($modules[$moduleKey]['inputs'][$fieldKey])) {
        Flash::error('Invalid input field name: ' . Text::h($fieldKey));
        continue;
    }

    if (isset($modules[$moduleKey]['gcModuleMultiple'][$fieldKey])) {
        foreach ($fields as $fieldVal => $field) {
            if ($fieldVal == 'new-0') unset($fields[$fieldVal]);
        }

        // sort fields so new fields come last, so that unique constraint errors appear on newer items
        uksort($fields, function($a, $b) {
            if (is_numeric($b) && !is_numeric($a)) return 1;
            if (!is_numeric($b) && is_numeric($a)) return -1;
            return 0;
        });
    }


    foreach ($fields as $fieldVal => $field) {

        // new fields
        if (is_string($fieldVal)) {
            if (substr($fieldVal, 0, 4) != 'new-') {
                Flash::error('Invalid new input field name: ' . Text::h($fieldKey) . '/' . Text::h($fieldVal));
                continue;
            }
            $newId = substr($fieldVal, 4);
            if (!ctype_digit($newId)) {
                Flash::error('Invalid new input field id: ' . Text::h($fieldKey) . '/' . Text::h($fieldVal));
                continue;
            }
            $newId = (int)$newId;

            if (!isset($modules[$moduleKey]['inputs'][$fieldKey]['new-0'])) {
                Flash::error('Missing new input field id in module: ' . Text::h($fieldKey) . '/new-0');
                continue;
            }

            if (isset($field['delete']) && $field['delete'] == 'on') continue;

            foreach ($field as $name => $val) {
                if ($name == 'delete') {
                    if (in_array($val, ['', 'on']))
                        $modules[$moduleKey]['inputs'][$fieldKey]['new-' . $newId]['delete'] = $val;
                    else
                        Flash::error('Invalid new input field delete: ' . Text::h($fieldKey) . '/' . Text::h($fieldVal) . '/' . Text::h($val));
                    continue;
                }
                if ($name == 'position') {
                    if (ctype_digit($val)) {
                        $modules[$moduleKey]['inputs'][$fieldKey]['new-' . $newId]['position'] = $val;
                        $fieldsNew[$moduleKey][$fieldKey][$fieldVal][$name] = $val;
                    } else {
                        Flash::error('Invalid new input field position: ' . Text::h($fieldKey) . '/' . Text::h($fieldVal) . '/' . Text::h($val));
                    }
                    continue;
                }

                $input = $modules[$moduleKey]['inputs'][$fieldKey]['new-0'][$name];
                $input['name'] = 'modules[' . $moduleKey . '][' . $fieldKey . '][' . $fieldVal . '][' . $name . ']';
                $input = Input::validate($input, $val, E::$itemId);

                $modules[$moduleKey]['inputs'][$fieldKey]['new-' . $newId][$name] = $input;

                if ($input['nullable'] && !$input['value'] && !$input['valueFromDb']) $input['valueFromDb'] = null;

                if ($input['value'] !== $input['valueFromDb'])
                    $fieldsNew[$moduleKey][$fieldKey][$fieldVal][$name] = $input['valueToDb'];

                // if ($input['dbReciprocal']) {
                //     geD($input);
                // }
            }
            continue;
        }

        if (!is_int($fieldVal)) {
            Flash::error('Invalid input field id - not numeric: ' . Text::h($fieldKey) . '/' . Text::h($fieldVal));
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
                    Flash::error('Invalid new input field delete: ' . Text::h($fieldKey) . '/' . Text::h($fieldVal) . '/' . Text::h($val));
                continue;
            }
            if ($name == 'position') {
                if (ctype_digit($val)) {
                    if ($modules[$moduleKey]['inputs'][$fieldKey][$fieldVal]['position'] != $val) {
                        $modules[$moduleKey]['inputs'][$fieldKey][$fieldVal]['position'] = $val;
                        $fieldsUpd[$moduleKey][$fieldKey][$fieldVal][$name] = $val;
                    }
                } else {
                    Flash::error('Invalid new input field position: ' . Text::h($fieldKey) . '/' . Text::h($fieldVal) . '/' . Text::h($val));
                }
                continue;
            }

            $input = Input::validate($modules[$moduleKey]['inputs'][$fieldKey][$fieldVal][$name], $val, E::$itemId);

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
                            return 0;
                        });
                    } else {
                        uasort($modules[$moduleKey]['inputs'][$fieldKey], function($a, $b) use ($colKey) {
                            return $b[$colKey]['value'] <=> $a[$colKey]['value'];
                        });
                        uksort($modules[$moduleKey]['inputs'][$fieldKey], function($a, $b) {
                            if (is_numeric($b) && !is_numeric($a)) return 1;
                            else if (!is_numeric($b) && is_numeric($a)) return -1;
                            return 0;
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

            $msg = Text::t('Must be unique. An item with that value already exists.');
            foreach ($visited as $fieldVal => $currentArray) {
                $fieldValSearch = array_search($currentArray, $visited);
                if ($fieldVal != $fieldValSearch) {
                    foreach ($currentArray as $unique => $values) {
                        Flash::error($msg, 'form', $modules[$moduleKey]['inputs'][$fieldKey][$fieldVal][$unique]['name']);
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






// return;
//
// foreach ($postModule as $fieldName => $fieldValue) {
//     foreach ($fieldValue as $name => $value) {
//         if (isset($modules[$moduleKey]['inputs'][$fieldName][$name])) {
//             $input = Input::validate($modules[$moduleKey]['inputs'][$fieldName][$name], $value, E::$itemId);
//
//             $modules[$moduleKey]['inputs'][$fieldName][$name] = $input;
//         } else if (isset($modules[$moduleKey]['inputsUnused'][$fieldName][$name])) {
//             $input = Input::validate($modules[$moduleKey]['inputsUnused'][$fieldName][$name], $value, E::$itemId);
//
//             $modules[$moduleKey]['inputsUnused'][$fieldName][$name] = $input;
//         } else {
//             $input['errors'][] = 'Non existant input.';
//         }
//
//         if (empty($input['errors']) && $input['dbUnique']) {
//             $query = Sql::selectFirst($module['gcSelect']);
//             $query .= Sql::selectWhere([$input['nameFromDb'] => '=']);
//             $query .= Sql::selectLimit(0, 1);
//
//             $stmt = G::prepare($query);
//             $stmt->bind_param('s', $input['value']);
//             $stmt->bind_result($rowId);
//             $stmt->execute();
//             $stmt->fetch();
//             $stmt->close();
//
//             if ($rowId && (string)$rowId != $input['value']) {
//                 $input['errors'][] = 'Must be unique. An item with that value already exists.';
//             }
//
//         }
//
//         foreach ($input['errors'] as $msg) {
//             Flash::error($msg, 'form', $input['name']);
//             if ($input['lang']) {
//                 $langSelectClass[$input['lang']] = 'btn-red';
//             }
//         }
//
//
//         $nameFromDb = $input['nameFromDb'];
//         $valueToDb =  $input['valueToDb'];
//
//         if ($input['value'] !== $input['valueFromDb']) {
//             $modules[$moduleKey]['inputs'][$fieldName][$name] = $input;
//         }
//
//     }
// }
//
