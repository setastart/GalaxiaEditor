<?php

use Galaxia\Director;

$dbSchema = $app->cacheGet('editor', 0, 'schema', 'default', '', function() use ($db, $app) {
    $array = [];
    $query = '
        SELECT TABLE_NAME, COLUMN_NAME, IS_NULLABLE, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLUMN_TYPE, COLUMN_KEY
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ?
    ';
    $stmt = $db->prepare($query);
    $stmt->bind_param('s', $app->mysqlDb);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($data = $result->fetch_assoc()) {
        if (!isset($array[$data['TABLE_NAME']])) $array[$data['TABLE_NAME']] = [];
        if (!isset($array[$data['TABLE_NAME']][$data['COLUMN_NAME']]))
            $array[$data['TABLE_NAME']][$data['COLUMN_NAME']] = [];

        $array[$data['TABLE_NAME']][$data['COLUMN_NAME']] = [
            'DATA_TYPE' => $data['DATA_TYPE'],
            'CHARACTER_MAXIMUM_LENGTH' => $data['CHARACTER_MAXIMUM_LENGTH'],
            'IS_NULLABLE' => $data['IS_NULLABLE'],
            'COLUMN_TYPE' => $data['COLUMN_TYPE'],
            'COLUMN_KEY' => $data['COLUMN_KEY']
        ];
    }
    $stmt->close();
    return $array;
}, Director::$debug);




// check database schema for required tables and columns

gcTableExists($dbSchema, '', 'page');
gcTableColumnExists($dbSchema, '', 'page', 'pageStatus');
gcTableColumnExists($dbSchema, '', 'page', 'pageType');
gcTableColumnExists($dbSchema, '', 'page', 'position');
foreach ($app->langs as $lang) {
    gcTableColumnExists($dbSchema, '', 'page', 'pageSlug_' . $lang);
    gcTableColumnExists($dbSchema, '', 'page', 'pageTitle_' . $lang);
}

gcTableExists($dbSchema, '', 'pageRedirect');

foreach ($dbSchema as $table => $columns) {
    foreach ($columns as $col => $colSchema) {

        if ($colSchema['DATA_TYPE'] == 'text' && $colSchema['IS_NULLABLE'] == 'NO') {
            error('schema: ' . $table . '/' . $col . ' - TEXT column should have IS_NULLABLE set');
            geD($table);
            geErrorPage(500, 'config schema error');
        }
    }
}






// check configuration

foreach ($geConf as $areaKey => $area) {

    if (!empty($area['gcList'])) {

        $itemsToCheckTableCols = ['gcSelect', 'gcSelectLJoin'];
        foreach ($itemsToCheckTableCols as $toCheck)
            foreach ($area['gcList'][$toCheck] as $table => $cols) {
                gcTableExists($dbSchema, $areaKey . '/gcList/' . $toCheck, $table);
                foreach ($cols as $col)
                    gcTableColumnExists($dbSchema, $areaKey . '/gcList/' . $toCheck, $table, $col);
            }

        foreach ($area['gcList']['gcSelectOrderBy'] as $table => $cols) {
            gcTableExists($dbSchema, $areaKey . '/gcList/gcSelectOrderBy', $table);
            foreach ($cols as $key => $col)
                gcTableColumnExists($dbSchema, $areaKey . '/gcList/gcSelectOrderBy', $table, $key);
        }

        foreach ($area['gcList']['gcColumns'] as $column)
            foreach ($column['gcColContent'] ?? [] as $rowCol) {
                gcTableExists($dbSchema, $areaKey . '/gcList/gcColumns', $rowCol['dbTab']);

                foreach ($rowCol['dbCols'] as $dbCol)
                    gcTableColumnExists($dbSchema, $areaKey . '/gcList/gcColumns', $rowCol['dbTab'], $dbCol);
            }

        foreach ($area['gcList']['gcFilterTexts'] as $column)
            foreach ($column['filterWhat'] as $table => $cols) {
                gcTableExists($dbSchema, $areaKey . '/gcList/gcFilterTexts', $table);
                foreach ($cols as $key => $col)
                    gcTableColumnExists($dbSchema, $areaKey . '/gcList/gcFilterTexts', $table, $col);
            }

        foreach ($area['gcList']['gcFilterInts'] as $column)
            foreach ($column['filterWhat'] as $table => $cols) {
                gcTableExists($dbSchema, $areaKey . '/gcList/gcFilterInts', $table);
                foreach ($cols as $key => $col)
                    gcTableColumnExists($dbSchema, $areaKey . '/gcList/gcFilterInts', $table, $col);
            }
    }


    if (!empty($area['gcItem'])) {
        $item = $area['gcItem'];
        gcTableExists($dbSchema, $areaKey . '/gcItem/gcTable', $item['gcTable']);
        gcTableColumnExists($dbSchema, $areaKey . '/gcItem', $item['gcTable'], $item['gcTable'] . 'Id');
        gcTableColumnExists($dbSchema, $areaKey . '/gcItem/gcColKey', $item['gcTable'], $item['gcColKey']);

        if ($item['gcRedirect']) {
            gcTableExists($dbSchema, $areaKey . '/gcItem/gcRedirect', $item['gcTable'] . 'Redirect');
            gcTableColumnExists($dbSchema, $areaKey . '/gcItem/gcRedirect', $item['gcTable'] . 'Redirect', $item['gcTable'] . 'RedirectId');
            gcTableColumnExists($dbSchema, $areaKey . '/gcItem/gcRedirect', $item['gcTable'] . 'Redirect', $item['gcTable'] . 'RedirectSlug');
        }

        $itemsToCheckTableCols = ['gcInsert', 'gcSelect', 'gcUpdate', 'gcDelete', 'gcSelectExtra'];
        foreach ($itemsToCheckTableCols as $toCheck)
            foreach ($item[$toCheck] as $table => $cols) {
                if ($table == 'gcPerms') continue;
                gcTableExists($dbSchema, $areaKey . '/gcItem/' . $toCheck, $table);
                foreach ($cols as $col)
                    gcTableColumnExists($dbSchema, $areaKey . '/gcItem/' . $toCheck, $table, $col);
            }

        foreach ($item['gcInfo'] as $inputCol => $input) {
            gcTableColumnInput($dbSchema, $areaKey . '/gcItem/gcInputs', $item['gcTable'], $inputCol, $input);
        }

        foreach ($item['gcInputs'] as $inputCol => $input) {
            gcTableColumnInput($dbSchema, $areaKey . '/gcItem/gcInputs', $item['gcTable'], $inputCol, $input);
        }

        foreach ($item['gcInputsWhere'] as $where => $fieldKeys) {
            gcTableColumnExists($dbSchema, $areaKey . '/gcItem/gcItemInputsWhere', $item['gcTable'], $where);
            foreach ($fieldKeys as $inputs)
                foreach ($inputs as $inputCol => $input)
                    gcTableColumnInput($dbSchema, $areaKey . '/gcItem/gcItemInputsWhere', $item['gcTable'], $inputCol, array_merge($item['gcInputs'][$inputCol] ?? [], $input));
        }


        foreach ($item['gcModules'] as $moduleId => $module) {
            $errorStringPrefix = $areaKey . '/gcItem/gcModules/' . $moduleId;

            gcTableExists($dbSchema, $errorStringPrefix . '/gcTable', $module['gcTable']);
            gcTableColumnExists($dbSchema, $errorStringPrefix . '/gcColKey', $module['gcTable'], $module['gcTable'] . 'Id');
            gcTableColumnExists($dbSchema, $errorStringPrefix, $module['gcTable'], 'fieldKey');
            gcTableColumnExists($dbSchema, $errorStringPrefix . '/position', $module['gcTable'], 'position');

            foreach ($module['gcModuleDeleteIfEmpty'] as $col)
                gcTableColumnExists($dbSchema, $errorStringPrefix . '/gcModuleDeleteIfEmpty', $module['gcTable'], $col);

            $foundMulti = false;
            foreach ($module['gcModuleMultiple'] as $moduleMultiple) {
                if ($moduleMultiple['reorder']) {
                    $foundMulti = true;
                    break;
                }
            }

            if ($foundMulti) {
                foreach ($module['gcSelect'] as $table => $cols) {
                    gcQueryColumnExists($errorStringPrefix . '/gcSelect', $table, $cols, 'fieldKey');
                    gcQueryColumnExists($errorStringPrefix . '/gcSelect', $table, $cols, 'position');
                }
                foreach ($module['gcUpdate'] as $table => $cols) {
                    // gcQueryColumnExists($errorStringPrefix . '/gcSelect', $table, $cols, 'position');
                }
            }


            $itemsToCheckTableCols = ['gcSelect', 'gcSelectLJoin', 'gcSelectExtra', 'gcUpdate'];
            foreach ($itemsToCheckTableCols as $toCheck)
                foreach ($module[$toCheck] as $table => $cols) {
                    if ($table == 'gcPerms') continue;
                    gcTableExists($dbSchema, $errorStringPrefix . '/' . $toCheck, $table);
                    foreach ($cols as $col)
                        gcTableColumnExists($dbSchema, $errorStringPrefix . '/' . $toCheck, $table, $col);
                }

            foreach ($module['gcSelectOrderBy'] as $table => $cols) {
                gcTableExists($dbSchema, $errorStringPrefix . '/gcSelectOrderBy', $table);
                foreach ($cols as $key => $col)
                    gcTableColumnExists($dbSchema, $errorStringPrefix . '/gcSelectOrderBy', $table, $key);
            }

            foreach ($module['gcInputs'] as $inputCol => $input)
                gcTableColumnInput($dbSchema, $errorStringPrefix . '/gcInputs', $module['gcTable'], $inputCol, $input);

            foreach ($module['gcInputsWhereCol'] as $whereCol => $inputs)
                foreach ($inputs as $inputCol => $input)
                    gcTableColumnInput($dbSchema, $errorStringPrefix . '/gcInputsWhereCol', $module['gcTable'], $inputCol, array_merge($module['gcInputs'][$inputCol] ?? [], $input));

            foreach ($module['gcInputsWhereParent'] as $parentCol => $parentVals) {
                gcTableColumnExists($dbSchema, $errorStringPrefix . '/gcInputsWhereParent', $item['gcTable'], $parentCol);
                foreach ($parentVals as $fieldKeys)
                    foreach ($fieldKeys as $inputs)
                        foreach ($inputs as $inputCol => $input)
                            gcTableColumnInput($dbSchema, $errorStringPrefix . '/gcInputsWhereParent', $module['gcTable'], $inputCol, array_merge($module['gcInputs'][$inputCol] ?? [], $input));
            }

            foreach ($module['gcModuleMultiple'] as $multi)
                foreach ($multi['unique'] as $unique)
                    gcTableColumnExists($dbSchema, $errorStringPrefix . '/gcModuleMultiple/unique', $table, $unique);

        }

    }

    if (!empty($area['gcImagesInUse'])) {
        foreach ($area['gcImagesInUse'] as $inUse => $queries) {
            gcTableExists($dbSchema, $areaKey . '/gcImagesInUse', $inUse);

            foreach (['gcSelect', 'gcSelectLJoin', 'gcSelectOrderBy'] as $toCheck)
                foreach ($queries[$toCheck] as $table => $cols) {
                    gcTableExists($dbSchema, $areaKey . '/gcList/' . $toCheck, $table);
                    foreach ($cols as $col)
                        gcTableColumnExists($dbSchema, $areaKey . '/gcList/' . $toCheck, $table, $col);
                }

            $itemsToCheckTableCols = ['gcSelect', 'gcSelectLJoin', 'gcSelectOrderBy'];
            foreach ($itemsToCheckTableCols as $toCheck)
                foreach ($area['gcImagesInUse'][$inUse][$toCheck] as $table => $cols) {
                    gcTableExists($dbSchema, $areaKey . '/gcImagesInUse/' . $toCheck, $table);
                    foreach ($cols as $col)
                        gcTableColumnExists($dbSchema, $areaKey . '/gcImagesInUse/' . $toCheck, $table, $col);
                }

        }
    }

    if ($area['gcPageType'] == 'gcpHistory') {
        $cols = ['_geHistoryId', '_geUserId', 'uniqueId', 'action', 'tabName', 'tabId', 'fieldKey', 'inputKey', 'content', 'timestampCreated'];
        foreach ($cols as $col)
            gcTableColumnExists($dbSchema, $areaKey . '/' . $col, '_geHistory', $col);
    }
}




function gcTableColumnInput($dbSchema, $errorString, $table, $col, $input) {
    $errorString .= '/' . $table . '/' . $col;

    if (!$input) return;
    if ($col == 'gcPerms') return;
    if (substr($col, 0, 8) == 'password') return;
    if (substr($col, 0, 8) == 'importer') return;

    gcTableColumnExists($dbSchema, $errorString, $table, $col);


    if (isset($input['nullable'])) {
        if ($input['nullable'] && $dbSchema[$table][$col]['IS_NULLABLE'] == 'NO') {
            error($errorString . ': input IS nullable but db table column is NOT.');
            geD($table, $col);
            geErrorPage(500, 'config schema error');
        } else if (!$input['nullable'] && $dbSchema[$table][$col]['IS_NULLABLE'] == 'YES') {
            error($errorString . ': input is NOT nullable but db table column IS.');
            geD($table, $col);
            geErrorPage(500, 'config schema error');
        }
    } else {
        if ($dbSchema[$table][$col]['IS_NULLABLE'] == 'YES') {
            error($errorString . ': input is NOT nullable but db table column IS.');
            geD($table, $col);
            geErrorPage(500, 'config schema error');
        }
    }
}


function gcTableExists($dbSchema, $errorString, $table) {
    $errorString .= '/' . $table;
    if (!isset($dbSchema[$table])) {
        error($errorString . ': db table missing.');
        geD($table);
        geErrorPage(500, 'config schema error');
    }
}


function gcTableColumnExists($dbSchema, $errorString, $table, $col) {
    $errorString .= '/' . $table . '/' . $col;
    if (!isset($dbSchema[$table][$col])) {
        error($errorString . ': db column missing.');
        geD('Table: ' . $table, 'Col: ' . $col);
        geErrorPage(500, 'config schema error');
    }
}


function gcQueryColumnExists($errorString, $table, $cols, $col) {
    $errorString .= '/' . $table . '/' . $col;
    if (!in_array($col, $cols)) {
        error($errorString . ': query column missing.');
        geD('Table: ' . $table, 'Col: ' . $col);
        geErrorPage(500, 'config schema error');
    }
}

