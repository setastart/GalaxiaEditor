<?php


use Galaxia\Flash;
use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


foreach (E::$itemPostModule as $fieldKey => $fields) {

    if (!isset(E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey])) {
        Flash::error('Invalid input field name: ' . Text::h($fieldKey));
        continue;
    }

    if (isset(E::$modules[E::$itemPostModuleKey]['gcModuleMultiple'][$fieldKey])) {
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
            if (!str_starts_with($fieldVal, 'new-')) {
                Flash::error('Invalid new input field name: ' . Text::h($fieldKey) . '/' . Text::h($fieldVal));
                continue;
            }
            $newId = substr($fieldVal, 4);
            if (!ctype_digit($newId)) {
                Flash::error('Invalid new input field id: ' . Text::h($fieldKey) . '/' . Text::h($fieldVal));
                continue;
            }
            $newId = (int)$newId;

            if (!isset(E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey]['new-0'])) {
                Flash::error('Missing new input field id in module: ' . Text::h($fieldKey) . '/new-0');
                continue;
            }

            if (isset($field['delete']) && $field['delete'] == 'on') continue;

            foreach ($field as $name => $val) {
                if ($name == 'delete') {
                    if (in_array($val, ['', 'on']))
                        E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey]['new-' . $newId]['delete'] = $val;
                    else
                        Flash::error('Invalid new input field delete: ' . Text::h($fieldKey) . '/' . Text::h($fieldVal) . '/' . Text::h($val));
                    continue;
                }
                if ($name == 'position') {
                    if (ctype_digit($val)) {
                        E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey]['new-' . $newId]['position'] = $val;
                        E::$fieldsNew[E::$itemPostModuleKey][$fieldKey][$fieldVal][$name] = $val;
                    } else {
                        Flash::error('Invalid new input field position: ' . Text::h($fieldKey) . '/' . Text::h($fieldVal) . '/' . Text::h($val));
                    }
                    continue;
                }

                $input = E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey]['new-0'][$name];
                $input['name'] = 'modules[' . E::$itemPostModuleKey . '][' . $fieldKey . '][' . $fieldVal . '][' . $name . ']';
                $input = Input::validate($input, $val, E::$itemId);

                E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey]['new-' . $newId][$name] = $input;

                if ($input['nullable'] && !$input['value'] && !$input['valueFromDb']) $input['valueFromDb'] = null;

                if ($input['value'] !== $input['valueFromDb'])
                    E::$fieldsNew[E::$itemPostModuleKey][$fieldKey][$fieldVal][$name] = $input['valueToDb'];

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
            E::$fieldsDel[E::$itemPostModuleKey][$fieldKey][] = $fieldVal;
            E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey][$fieldVal]['delete'] = 'on';
            continue;
        }

        // updated fields
        foreach ($field as $name => $val) {
            if ($name == 'delete') {
                if (in_array($val, ['', 'on']))
                    E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey][$fieldVal]['delete'] = $val;
                else
                    Flash::error('Invalid new input field delete: ' . Text::h($fieldKey) . '/' . Text::h($fieldVal) . '/' . Text::h($val));
                continue;
            }
            if ($name == 'position') {
                if (ctype_digit($val)) {
                    if (E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey][$fieldVal]['position'] != $val) {
                        E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey][$fieldVal]['position'] = $val;
                        E::$fieldsUpd[E::$itemPostModuleKey][$fieldKey][$fieldVal][$name] = $val;
                    }
                } else {
                    Flash::error('Invalid new input field position: ' . Text::h($fieldKey) . '/' . Text::h($fieldVal) . '/' . Text::h($val));
                }
                continue;
            }

            $input = Input::validate(E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey][$fieldVal][$name], $val, E::$itemId);

            E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey][$fieldVal][$name] = $input;

            if ($input['nullable'] && !$input['value'] && !$input['valueFromDb']) $input['valueFromDb'] = null;

            if ($input['value'] !== $input['valueFromDb'])
                E::$fieldsUpd[E::$itemPostModuleKey][$fieldKey][$fieldVal][$name] = $input['valueToDb'];

        }

    }

    // reorder fields
    if (isset(E::$modules[E::$itemPostModuleKey]['gcModuleMultiple'][$fieldKey])) {
        if (E::$modules[E::$itemPostModuleKey]['gcModuleMultiple'][$fieldKey]['reorder']) {
            uasort(E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey], function($a, $b) {
                return $a['position'] <=> $b['position'];
            });
        } else {
            $orderBy = array_reverse(E::$modules[E::$itemPostModuleKey]['gcSelectOrderBy']);
            foreach ($orderBy as $tabKey => $cols) {
                foreach ($cols as $colKey => $colSort) {
                    if (!isset(E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey][$colKey])) continue;
                    if ($colSort == 'ASC') {
                        uasort(E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey], function($a, $b) use ($colKey) {
                            return $a[$colKey]['value'] <=> $b[$colKey]['value'];
                        });
                        uksort(E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey], function($a, $b) {
                            if (is_numeric($a) && !is_numeric($b)) return 1;
                            else if (!is_numeric($a) && is_numeric($b)) return -1;
                            return 0;
                        });
                    } else {
                        uasort(E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey], function($a, $b) use ($colKey) {
                            return $b[$colKey]['value'] <=> $a[$colKey]['value'];
                        });
                        uksort(E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey], function($a, $b) {
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
    if (isset(E::$modules[E::$itemPostModuleKey]['gcModuleMultiple'][$fieldKey])) {
        if (E::$modules[E::$itemPostModuleKey]['gcModuleMultiple'][$fieldKey]['unique']) {
            $visited = [];
            foreach ($fields as $fieldVal => $field) {
                if (!isset(E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey][$fieldVal])) continue;
                if (isset($field['delete']) && $field['delete'] == 'on') continue;
                foreach (E::$modules[E::$itemPostModuleKey]['gcModuleMultiple'][$fieldKey]['unique'] as $unique) {
                    $val = E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey][$fieldVal][$unique]['value'];
                    $visited[$fieldVal][$unique] = $val;
                }
            }

            $msg = Text::t('Must be unique. An item with that value already exists.');
            foreach ($visited as $fieldVal => $currentArray) {
                $fieldValSearch = array_search($currentArray, $visited);
                if ($fieldVal != $fieldValSearch) {
                    foreach ($currentArray as $unique => $values) {
                        Flash::error($msg, 'form', E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey][$fieldVal][$unique]['name']);
                        E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey][$fieldVal][$unique]['errors'][] = $msg;
                        if (E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey][$fieldVal][$unique]['lang']) {
                            E::$langSelectClass[E::$modules[E::$itemPostModuleKey]['inputs'][$fieldKey][$fieldVal][$unique]['lang']] = 'btn-red';
                        }
                    }
                }
            }
        }
    }

}






// return;
//
// foreach (E::$itemPostModule as $fieldName => $fieldValue) {
//     foreach ($fieldValue as $name => $value) {
//         if (isset(E::$modules[E::$itemPostModuleKey]['inputs'][$fieldName][$name])) {
//             $input = Input::validate(E::$modules[E::$itemPostModuleKey]['inputs'][$fieldName][$name], $value, E::$itemId);
//
//             E::$modules[E::$itemPostModuleKey]['inputs'][$fieldName][$name] = $input;
//         } else if (isset(E::$modules[E::$itemPostModuleKey]['inputsUnused'][$fieldName][$name])) {
//             $input = Input::validate(E::$modules[E::$itemPostModuleKey]['inputsUnused'][$fieldName][$name], $value, E::$itemId);
//
//             E::$modules[E::$itemPostModuleKey]['inputsUnused'][$fieldName][$name] = $input;
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
//                 E::$langSelectClass[$input['lang']] = 'btn-red';
//             }
//         }
//
//
//         $nameFromDb = $input['nameFromDb'];
//         $valueToDb =  $input['valueToDb'];
//
//         if ($input['value'] !== $input['valueFromDb']) {
//             E::$modules[E::$itemPostModuleKey]['inputs'][$fieldName][$name] = $input;
//         }
//
//     }
// }
//
