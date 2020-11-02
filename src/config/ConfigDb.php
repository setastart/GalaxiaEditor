<?php


namespace GalaxiaEditor\config;


use Galaxia\Director;


class ConfigDb {

    static function validate(array $geConf) {
        $db  = Director::getMysqli();
        $app = Director::getApp();

        $dbSchema = [];

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
            if (!isset($dbSchema[$data['TABLE_NAME']])) $dbSchema[$data['TABLE_NAME']] = [];
            if (!isset($dbSchema[$data['TABLE_NAME']][$data['COLUMN_NAME']]))
                $dbSchema[$data['TABLE_NAME']][$data['COLUMN_NAME']] = [];

            $dbSchema[$data['TABLE_NAME']][$data['COLUMN_NAME']] = [
                'DATA_TYPE'                => $data['DATA_TYPE'],
                'CHARACTER_MAXIMUM_LENGTH' => $data['CHARACTER_MAXIMUM_LENGTH'],
                'IS_NULLABLE'              => $data['IS_NULLABLE'],
                'COLUMN_TYPE'              => $data['COLUMN_TYPE'],
                'COLUMN_KEY'               => $data['COLUMN_KEY'],
            ];
        }
        $stmt->close();




        // check database schema for required tables and columns

        ConfigDb::gcTableExists($dbSchema, '', 'page');
        ConfigDb::gcTableColumnExists($dbSchema, '', 'page', 'pageStatus');
        ConfigDb::gcTableColumnExists($dbSchema, '', 'page', 'pageType');
        ConfigDb::gcTableColumnExists($dbSchema, '', 'page', 'position');
        foreach ($app->langs as $lang) {
            ConfigDb::gcTableColumnExists($dbSchema, '', 'page', 'pageSlug_' . $lang);
            ConfigDb::gcTableColumnExists($dbSchema, '', 'page', 'pageTitle_' . $lang);
        }

        ConfigDb::gcTableExists($dbSchema, '', 'pageRedirect');

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
                        ConfigDb::gcTableExists($dbSchema, $areaKey . '/gcList/' . $toCheck, $table);
                        foreach ($cols as $col)
                            ConfigDb::gcTableColumnExists($dbSchema, $areaKey . '/gcList/' . $toCheck, $table, $col);
                    }

                foreach ($area['gcList']['gcSelectOrderBy'] as $table => $cols) {
                    ConfigDb::gcTableExists($dbSchema, $areaKey . '/gcList/gcSelectOrderBy', $table);
                    foreach ($cols as $key => $col)
                        ConfigDb::gcTableColumnExists($dbSchema, $areaKey . '/gcList/gcSelectOrderBy', $table, $key);
                }

                foreach ($area['gcList']['gcColumns'] as $column)
                    foreach ($column['gcColContent'] ?? [] as $rowCol) {
                        ConfigDb::gcTableExists($dbSchema, $areaKey . '/gcList/gcColumns', $rowCol['dbTab']);

                        foreach ($rowCol['dbCols'] as $dbCol)
                            ConfigDb::gcTableColumnExists($dbSchema, $areaKey . '/gcList/gcColumns', $rowCol['dbTab'], $dbCol);
                    }

                foreach ($area['gcList']['gcFilterTexts'] as $column)
                    foreach ($column['filterWhat'] as $table => $cols) {
                        ConfigDb::gcTableExists($dbSchema, $areaKey . '/gcList/gcFilterTexts', $table);
                        foreach ($cols as $key => $col)
                            ConfigDb::gcTableColumnExists($dbSchema, $areaKey . '/gcList/gcFilterTexts', $table, $col);
                    }

                foreach ($area['gcList']['gcFilterInts'] as $column)
                    foreach ($column['filterWhat'] as $table => $cols) {
                        ConfigDb::gcTableExists($dbSchema, $areaKey . '/gcList/gcFilterInts', $table);
                        foreach ($cols as $key => $col)
                            ConfigDb::gcTableColumnExists($dbSchema, $areaKey . '/gcList/gcFilterInts', $table, $col);
                    }
            }


            if (!empty($area['gcItem'])) {
                $item = $area['gcItem'];
                ConfigDb::gcTableExists($dbSchema, $areaKey . '/gcItem/gcTable', $item['gcTable']);
                ConfigDb::gcTableColumnExists($dbSchema, $areaKey . '/gcItem', $item['gcTable'], $item['gcTable'] . 'Id');

                if (is_array($item['gcColKey'])) {
                    foreach ($item['gcColKey'] as $langTitle) {
                        ConfigDb::gcTableColumnExists($dbSchema, $areaKey . '/gcItem/gcColKey', $item['gcTable'], $langTitle);
                    }
                } else {
                    ConfigDb::gcTableColumnExists($dbSchema, $areaKey . '/gcItem/gcColKey', $item['gcTable'], $item['gcColKey']);
                }

                if ($item['gcRedirect']) {
                    ConfigDb::gcTableExists($dbSchema, $areaKey . '/gcItem/gcRedirect', $item['gcTable'] . 'Redirect');
                    ConfigDb::gcTableColumnExists($dbSchema, $areaKey . '/gcItem/gcRedirect', $item['gcTable'] . 'Redirect', $item['gcTable'] . 'RedirectId');
                    ConfigDb::gcTableColumnExists($dbSchema, $areaKey . '/gcItem/gcRedirect', $item['gcTable'] . 'Redirect', $item['gcTable'] . 'RedirectSlug');
                }

                $itemsToCheckTableCols = ['gcInsert', 'gcSelect', 'gcUpdate', 'gcDelete', 'gcSelectExtra'];
                foreach ($itemsToCheckTableCols as $toCheck)
                    foreach ($item[$toCheck] as $table => $cols) {
                        if ($table == 'gcPerms') continue;
                        ConfigDb::gcTableExists($dbSchema, $areaKey . '/gcItem/' . $toCheck, $table);
                        foreach ($cols as $col)
                            ConfigDb::gcTableColumnExists($dbSchema, $areaKey . '/gcItem/' . $toCheck, $table, $col);
                    }

                foreach ($item['gcInfo'] as $inputCol => $input) {
                    ConfigDb::gcTableColumnInput($dbSchema, $areaKey . '/gcItem/gcInputs', $item['gcTable'], $inputCol, $input);
                }

                foreach ($item['gcInputs'] as $inputCol => $input) {
                    ConfigDb::gcTableColumnInput($dbSchema, $areaKey . '/gcItem/gcInputs', $item['gcTable'], $inputCol, $input);
                }

                foreach ($item['gcInputsWhere'] as $where => $fieldKeys) {
                    ConfigDb::gcTableColumnExists($dbSchema, $areaKey . '/gcItem/gcItemInputsWhere', $item['gcTable'], $where);
                    foreach ($fieldKeys as $inputs)
                        foreach ($inputs as $inputCol => $input)
                            ConfigDb::gcTableColumnInput($dbSchema, $areaKey . '/gcItem/gcItemInputsWhere', $item['gcTable'], $inputCol, array_merge($item['gcInputs'][$inputCol] ?? [], $input));
                }


                foreach ($item['gcModules'] as $moduleId => $module) {
                    $errorStringPrefix = $areaKey . '/gcItem/gcModules/' . $moduleId;

                    ConfigDb::gcTableExists($dbSchema, $errorStringPrefix . '/gcTable', $module['gcTable']);
                    ConfigDb::gcTableColumnExists($dbSchema, $errorStringPrefix . '/gcColKey', $module['gcTable'], $module['gcTable'] . 'Id');
                    ConfigDb::gcTableColumnExists($dbSchema, $errorStringPrefix, $module['gcTable'], ['fieldKey', $module['gcTable'] . 'Field']);
                    ConfigDb::gcTableColumnExists($dbSchema, $errorStringPrefix . '/position', $module['gcTable'], 'position');

                    foreach ($module['gcModuleDeleteIfEmpty'] as $col)
                        ConfigDb::gcTableColumnExists($dbSchema, $errorStringPrefix . '/gcModuleDeleteIfEmpty', $module['gcTable'], $col);

                    $foundMulti = false;
                    foreach ($module['gcModuleMultiple'] as $moduleMultiple) {
                        if ($moduleMultiple['reorder']) {
                            $foundMulti = true;
                            break;
                        }
                    }

                    if ($foundMulti) {
                        foreach ($module['gcSelect'] as $table => $cols) {
                            ConfigDb::gcQueryColumnExists($errorStringPrefix . '/gcSelect', $table, $cols, ['fieldKey', $module['gcTable'] . 'Field']);
                            ConfigDb::gcQueryColumnExists($errorStringPrefix . '/gcSelect', $table, $cols, 'position');
                        }
                        foreach ($module['gcUpdate'] as $table => $cols) {
                            // gcQueryColumnExists($errorStringPrefix . '/gcSelect', $table, $cols, 'position');
                        }
                    }


                    $itemsToCheckTableCols = ['gcSelect', 'gcSelectLJoin', 'gcSelectExtra', 'gcUpdate'];
                    foreach ($itemsToCheckTableCols as $toCheck)
                        foreach ($module[$toCheck] as $table => $cols) {
                            if ($table == 'gcPerms') continue;
                            ConfigDb::gcTableExists($dbSchema, $errorStringPrefix . '/' . $toCheck, $table);
                            foreach ($cols as $col)
                                ConfigDb::gcTableColumnExists($dbSchema, $errorStringPrefix . '/' . $toCheck, $table, $col);
                        }

                    foreach ($module['gcSelectOrderBy'] as $table => $cols) {
                        ConfigDb::gcTableExists($dbSchema, $errorStringPrefix . '/gcSelectOrderBy', $table);
                        foreach ($cols as $key => $col)
                            ConfigDb::gcTableColumnExists($dbSchema, $errorStringPrefix . '/gcSelectOrderBy', $table, $key);
                    }

                    foreach ($module['gcInputs'] as $inputCol => $input)
                        ConfigDb::gcTableColumnInput($dbSchema, $errorStringPrefix . '/gcInputs', $module['gcTable'], $inputCol, $input);

                    foreach ($module['gcInputsWhereCol'] as $whereCol => $inputs)
                        foreach ($inputs as $inputCol => $input)
                            ConfigDb::gcTableColumnInput($dbSchema, $errorStringPrefix . '/gcInputsWhereCol', $module['gcTable'], $inputCol, array_merge($module['gcInputs'][$inputCol] ?? [], $input));

                    foreach ($module['gcInputsWhereParent'] as $parentCol => $parentVals) {
                        ConfigDb::gcTableColumnExists($dbSchema, $errorStringPrefix . '/gcInputsWhereParent', $item['gcTable'], $parentCol);
                        foreach ($parentVals as $fieldKeys)
                            foreach ($fieldKeys as $inputs)
                                foreach ($inputs as $inputCol => $input)
                                    ConfigDb::gcTableColumnInput($dbSchema, $errorStringPrefix . '/gcInputsWhereParent', $module['gcTable'], $inputCol, array_merge($module['gcInputs'][$inputCol] ?? [], $input));
                    }

                    foreach ($module['gcModuleMultiple'] as $multi)
                        foreach ($multi['unique'] as $unique)
                            ConfigDb::gcTableColumnExists($dbSchema, $errorStringPrefix . '/gcModuleMultiple/unique', $table, $unique);

                }

            }

            if (!empty($area['gcImagesInUse'])) {
                foreach ($area['gcImagesInUse'] as $inUse => $queries) {
                    ConfigDb::gcTableExists($dbSchema, $areaKey . '/gcImagesInUse', $inUse);

                    foreach (['gcSelect', 'gcSelectLJoin', 'gcSelectOrderBy'] as $toCheck)
                        foreach ($queries[$toCheck] as $table => $cols) {
                            ConfigDb::gcTableExists($dbSchema, $areaKey . '/gcList/' . $toCheck, $table);
                            foreach ($cols as $col)
                                ConfigDb::gcTableColumnExists($dbSchema, $areaKey . '/gcList/' . $toCheck, $table, $col);
                        }

                    $itemsToCheckTableCols = ['gcSelect', 'gcSelectLJoin', 'gcSelectOrderBy'];
                    foreach ($itemsToCheckTableCols as $toCheck)
                        foreach ($area['gcImagesInUse'][$inUse][$toCheck] as $table => $cols) {
                            ConfigDb::gcTableExists($dbSchema, $areaKey . '/gcImagesInUse/' . $toCheck, $table);
                            foreach ($cols as $col)
                                ConfigDb::gcTableColumnExists($dbSchema, $areaKey . '/gcImagesInUse/' . $toCheck, $table, $col);
                        }

                }
            }

            if ($area['gcPageType'] == 'gcpHistory') {
                $cols = ['_geHistoryId', '_geUserId', 'uniqueId', 'action', 'tabName', 'tabId', 'fieldKey', 'inputKey', 'content', 'timestampCreated'];
                foreach ($cols as $col)
                    ConfigDb::gcTableColumnExists($dbSchema, $areaKey . '/' . $col, '_geHistory', $col);
            }
        }

    }

    public static function gcTableColumnInput($dbSchema, $errorString, $table, $col, $input) {
        $errorString .= '/' . $table . '/' . $col;

        if (!$input) return;
        if ($col == 'gcPerms') return;
        if (substr($col, 0, 8) == 'password') return;
        if (substr($col, 0, 8) == 'importer') return;

        ConfigDb::gcTableColumnExists($dbSchema, $errorString, $table, $col);


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

    public static function gcTableExists($dbSchema, $errorString, $table) {
        $errorString .= '/' . $table;
        if (!isset($dbSchema[$table])) {
            error($errorString . ': db table missing.');
            geD($table);
            geErrorPage(500, 'config schema error');
        }
    }

    public static function gcTableColumnExists(array $dbSchema, string $errorString, string $table, $cols) {
        if (is_string($cols)) $cols = [$cols];

        $found = false;
        foreach ($cols as $col) {
            if (isset($dbSchema[$table][$col])) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $col         = implode(', ', $cols);
            $errorString .= '/' . $table . '/' . $col;
            error($errorString . ': db column missing.');
            geD('Table: ' . $table, 'Col: ' . $col);
            geErrorPage(500, 'config schema error');
        }
    }

    public static function gcQueryColumnExists($errorString, $table, $colsExisting, $cols) {
        if (is_string($cols)) $cols = [$cols];

        $found = false;
        foreach ($cols as $col) {
            if (in_array($col, $colsExisting)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $col         = implode(', ', $cols);
            $errorString .= '/' . $table . '/' . $col;
            error($errorString . ': query column missing.');
            geD('Table: ' . $table, 'Col: ' . $col);
            geErrorPage(500, 'config schema error');
        }
    }




}
