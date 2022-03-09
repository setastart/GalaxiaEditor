<?php


use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\history\History;
use GalaxiaEditor\model\ModelField;


$itemColId = E::$item['gcTable'] . 'Id';


// insert fields

foreach (E::$fieldsNew as $moduleKey => $fields) {
    $module   = E::$modules[$moduleKey];
    $fieldCol = 'fieldKey';
    if (in_array($module['gcTable'] . 'Field', $module['gcSelect'][$module['gcTable']])) {
        $fieldCol = $module['gcTable'] . 'Field';
    }

    foreach ($fields as $fieldKey => $inserts) {
        foreach ($inserts as $fieldVal => $insert) {

            // if ($module['inputs'][$fieldKey][$fieldVal][array_key_first($insert)]['dbReciprocal'] ?? '') {
            //     try {
            //         $reciprocalInsert = $insert;
            //         $reciprocalItemId = $reciprocalInsert[array_key_first($reciprocalInsert)];
            //         $reciprocalInsert[array_key_first($reciprocalInsert)] = E::$itemId;
            //         $insertedId = ModelField::insert(
            //             $module['gcUpdate'],
            //             $itemColId, $reciprocalItemId,
            //             $fieldCol, $fieldKey,
            //             $reciprocalInsert
            //         );
            //     } catch (Exception $e) {
            //         return;
            //     }
            // }

            $insertedId = null;
            try {
                $insertedId = ModelField::insert(
                    $module['gcUpdate'],
                    $itemColId, E::$itemId,
                    $fieldCol, $fieldKey,
                    $insert
                );
            } catch (Exception $e) {
                Flash::error($e->getMessage());

                return;
            }

            if (is_null($insertedId)) {
                Flash::error('fields-update - Unable to insert field.');
                // geD($query);

                return;
            } else {
                Flash::info(sprintf(Text::t('Added field: %s.'), Text::t($fieldKey)));
                foreach ($insert as $inputName => $value) {
                    if ($inputName == 'position') continue;
                    if (!isset($module['inputs'][$fieldKey][$fieldVal][$inputName]['name'])) continue;
                    Flash::info(Text::t('Added'), 'form', 'modules[' . $moduleKey . '][' . $fieldKey . '][' . $insertedId . '][' . $inputName . ']');
                }
            }

        }
    }
}


// delete fields

foreach (E::$fieldsDel as $moduleKey => $fields) {
    $module   = E::$modules[$moduleKey];
    $fieldCol = 'fieldKey';
    if (in_array($module['gcTable'] . 'Field', $module['gcSelect'][$module['gcTable']])) {
        $fieldCol = $module['gcTable'] . 'Field';
    }

    foreach ($fields as $fieldKey => $deleteIds) {

        $query = Sql::deleteIn(
            $module['gcTable'],
            [$itemColId, $fieldCol],
            $module['gcTable'] . 'Id',
            $deleteIds
        );

        try {
            $stmt = G::prepare($query);
            $stmt->bind_param('ss' . str_repeat('d', count($deleteIds)), E::$itemId, $fieldKey, ...$deleteIds);
            $success    = $stmt->execute();
            $insertedId = $stmt->insert_id;
            $stmt->close();
            Flash::info(sprintf(Text::t('Deleted field: %s.'), Text::t($fieldKey)));

        } catch (Exception $e) {
            Flash::error('fields-update - Unable to delete fields.');
            Flash::error($e->getMessage());

            return;
        }

    }
}



// update fields

foreach (E::$fieldsUpd as $moduleKey => $fields) {
    $module   = E::$modules[$moduleKey];
    $fieldCol = 'fieldKey';
    if (in_array($module['gcTable'] . 'Field', $module['gcSelect'][$module['gcTable']])) {
        $fieldCol = $module['gcTable'] . 'Field';
    }

    foreach ($fields as $fieldKey => $updates) {
        foreach ($updates as $fieldVal => $update) {
            $queryUpdateWhere = [$module['gcTable'] => [$module['gcTable'] . 'Id', $itemColId, $fieldCol]];
            $params           = array_values($update);
            array_push($params, $fieldVal, E::$itemId, $fieldKey);

            $affectedRows = 0;
            try {
                $affectedRows = ModelField::update($module['gcUpdate'], $queryUpdateWhere, $params, $update);
            } catch (Exception $e) {
                Flash::error($e->getMessage());

                return;
            }

            if ($affectedRows < 1) {
                Flash::error('fields-update - Unable to update database.');
            } else {
                Flash::info(sprintf(Text::t('Updated field: %s.'), Text::t($fieldKey)));

                foreach ($update as $inputName => $value) {
                    if ($inputName == 'position') continue;

                    $lang = isset($module['inputs'][$fieldKey][$fieldVal][$inputName]['lang']) ? $module['inputs'][$fieldKey][$fieldVal][$inputName]['lang'] . ' - ' : '';
                    if ($value) {
                        Flash::info(Text::t('Updated'), 'form', $module['inputs'][$fieldKey][$fieldVal][$inputName]['name']);
                    } else {
                        Flash::info(Text::t('Deleted'), 'form', 'modules[' . $moduleKey . '][' . $fieldKey . '][new-0][' . $inputName . ']');
                    }

                    if (E::$item['gcTable'] == '_geUser') continue;
                    History::insert(E::$uniqueId, E::$item['gcTable'], E::$itemId, $inputName, $fieldKey, 2, $value, G::$me->id);
                }
            }

        }
    }
}




// delete fields with empty values

foreach (E::$modules as $module) {
    if (!empty($module['gcModuleDeleteIfEmpty'])) {

        $cols = [$itemColId];
        foreach ($module['gcModuleDeleteIfEmpty'] as $col)
            $cols[] = $col;

        $expression = [$module['gcTable'] => $cols];

        $query = Sql::deleteOrNull($expression);

        $params = [E::$itemId];
        foreach ($module['gcModuleDeleteIfEmpty'] as $col)
            $params[] = str_ends_with($col, 'Id') ? 0 : '';

        try {
            $stmt  = G::prepare($query);
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $affectedRows = $stmt->affected_rows;
            $stmt->close();

            if ($affectedRows > 0) {
                Flash::info(sprintf(Text::t('Empty fields deleted: %d'), $affectedRows));
            }

        } catch (Exception $e) {
            Flash::error('fields-update - Unable to delete empty fields.');
            geD($query, $params);
            Flash::error($e->getMessage());

            return;
        }

    }
}
