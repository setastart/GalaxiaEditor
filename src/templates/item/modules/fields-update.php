<?php


// insert fields
$itemColId = $item['gcTable'] . 'Id';

foreach ($fieldsNew as $moduleKey => $fields) {
    $module = $modules[$moduleKey];
    foreach ($fields as $fieldKey => $inserts) {
        foreach ($inserts as $fieldVal => $insert) {
            $values = [];
            $insert = array_merge([$itemColId => $itemId], ['fieldKey' => $fieldKey], $insert);

            foreach ($insert as $inputName => $value) {
                $values[] = $value;
            }

            $query = queryInsert($module['gcUpdate'], $insert);

            try {
                $stmt = $db->prepare($query);
                $types = str_repeat('s', count($values));
                $stmt->bind_param($types, ...$values);
                $success = $stmt->execute();
                $insertedId = $stmt->insert_id;
                $stmt->close();
                info(sprintf(t('Added field: %s.'), t($fieldKey)));
                foreach ($insert as $inputName => $value) {
                    if ($inputName == 'position') continue;
                    if (!isset($module['inputs'][$fieldKey][$fieldVal][$inputName]['name'])) continue;
                    info(t('Added'), 'form', 'modules[' . $moduleKey . '][' . $fieldKey . '][' . $insertedId . '][' . $inputName. ']');
                }

            } catch (Exception $e) {
                error('fields-update - Unable to insert field.');
                error($e->getMessage());
                return;
            }

        }
    }
}


// delete fields

foreach ($fieldsDel as $moduleKey => $fields) {
    $module = $modules[$moduleKey];
    foreach ($fields as $fieldKey => $deleteIds) {

        $query = queryDeleteIn(
            $module['gcTable'],
            [$itemColId, 'fieldKey'],
            $module['gcTable'] . 'Id',
            $deleteIds
        );

        try {
            $stmt = $db->prepare($query);
            $stmt->bind_param('ss' . str_repeat('d', count($deleteIds)), $itemId, $fieldKey, ...$deleteIds);
            $success = $stmt->execute();
            $insertedId = $stmt->insert_id;
            $stmt->close();
            info(sprintf(t('Deleted field: %s.'), t($fieldKey)));

        } catch (Exception $e) {
            error('fields-update - Unable to delete fields.');
            error($e->getMessage());
            return;
        }

    }
}



// update fields

foreach ($fieldsUpd as $moduleKey => $fields) {
    $module = $modules[$moduleKey];
    foreach ($fields as $fieldKey => $updates) {
        foreach ($updates as $fieldVal => $update) {
            $queryUpdateWhere = [$module['gcTable'] => [$module['gcTable'] . 'Id', $itemColId, 'fieldKey']];
            $params = array_values($update);
            array_push($params, $fieldVal, $itemId, $fieldKey);

            $query = queryUpdate($module['gcUpdate']);
            $query .= queryUpdateSet(array_keys($update));
            $query .= queryUpdateWhere($queryUpdateWhere);

            try {
                $stmt = $db->prepare($query);
                $types = str_repeat('s', count($update)) . 'dds';
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $affectedRows = $stmt->affected_rows;
                $stmt->close();

                if ($affectedRows < 1) {
                    error('fields-update - Unable to update database.');
                } else {
                    info(sprintf(t('Updated field: %s.'), t($fieldKey)));

                    foreach ($update as $inputName => $value) {
                        $lang = isset($module['inputs'][$fieldKey][$fieldVal][$inputName]['lang']) ? $module['inputs'][$fieldKey][$fieldVal][$inputName]['lang'] . ' - ': '';
                        if ($value) {
                            info(t('Updated'), 'form', $module['inputs'][$fieldKey][$fieldVal][$inputName]['name']);
                        } else {
                            info(t('Deleted'), 'form', 'modules[' . $moduleKey . '][' . $fieldKey . '][new-0][' . $inputName. ']');
                        }

                        if ($item['gcTable'] == '_geUser') continue;
                        insertHistory($uniqueId, $item['gcTable'], $itemId, $inputName, $fieldKey, 2, $value, $me->id);
                    }
                }
            } catch (Exception $e) {
                error('fields-update - Unable to save changes to field.');
                error($e->getMessage());
                return;
            }

        }
    }
}





// delete fields with empty values

if (!empty($module['gcModuleDeleteIfEmpty'])) {

    $cols = [$itemColId];
    foreach ($module['gcModuleDeleteIfEmpty'] as $col)
        $cols[] = $col;

    $expression = [$module['gcTable'] => $cols];

    $query = queryDeleteOrNull($expression);

    $params = [$itemId];
    foreach ($module['gcModuleDeleteIfEmpty'] as $col)
        $params[] = '';

    try {
        $stmt = $db->prepare($query);
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        if ($affectedRows > 0) {
            info(sprintf(t('Empty fields deleted: %d'), $affectedRows));
        }

    } catch (Exception $e) {
        error('fields-update - Unable to delete empty fields.');
        geD($query);
        error($e->getMessage());
        return;
    }

}
