<?php

use Galaxia\{AppImage, Director, Pagination, Sql, Text};


// ajax

if (Director::$ajax) {
    $editor->layout = 'none';
    $editor->view   = 'list/results';
}




// setup list

$list        = $geConf[$pgSlug]['gcList'];
$firstTable  = key($list['gcSelect']);
$firstColumn = $list['gcSelect'][$firstTable][0];


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

// get items from database using cache

$items = $app->cacheGet('editor', 2, 'list-' . $pgSlug . '-items', function() use ($db, $list, $firstTable, $firstColumn, $dbSchema) {
    // // add key columns to joined tables (used to group joins in columns)
    // $selectQueryWithJoinKeys = $list['gcSelect'];
    // foreach ($selectQueryWithJoinKeys as $table => $columns) {
    //     $keyCol = $table . 'Id';
    //     if (!in_array($keyCol, $columns)) array_unshift($selectQueryWithJoinKeys[$table], $keyCol);
    // }
    //
    //
    // $query = querySelect($selectQueryWithJoinKeys);
    // $query .= querySelectLeftJoinUsing($list['gcSelectLJoin'] ?? []);
    // $query .= querySelectOrderBy($list['gcSelectOrderBy'] ?? []);
    //
    // $f = function(mysqli_result $result, &$items) use ($list, $firstColumn) {
    //     while ($data = $result->fetch_assoc()) {
    //         $data = array_map('strval', $data);
    //         foreach ($list['gcSelect'] as $table => $columns) {
    //             foreach ($columns as $column) {
    //                 $items[$data[$firstColumn]][$table][$data[$table . 'Id']][$column] = $data[$column];
    //             }
    //         }
    //     }
    // };
    // $items = chunkSelectQuery($db, $query, $f);




    // add key columns to joined tables (used to group joins in columns)
    $selectQuery = $list['gcSelect'];
    $items       = [];
    $i           = 0;
    foreach ($selectQuery as $table => $columns) {
        Director::timerStart('list ' . $table);
        $keyCol = $table . 'Id';
        if (!in_array($keyCol, $columns)) array_unshift($selectQuery[$table], $keyCol);

        if ($i == 0) {

            $queryMain = [$table => $selectQuery[$table]];
            $query     = Sql::select($queryMain);
            if (isset($list['gcSelectOrderBy'][$table])) {
                $query .= Sql::selectOrderBy([$table => $list['gcSelectOrderBy'][$table]]);
            }

            $f = function(mysqli_result $result, &$items) use ($list, $firstColumn, $table, $queryMain) {
                while ($data = $result->fetch_assoc()) {
                    $data = array_map('strval', $data);
                    foreach ($queryMain[$table] as $column) {
                        $items[$data[$firstColumn]][$table][$data[$table . 'Id']][$column] = $data[$column];
                    }
                }
            };

            $items = Sql::chunkSelect($db, $query, $f, $items);

        } else {

            $queryJoin = [$firstTable => [$firstColumn], $table => $selectQuery[$table]];
            $query     = Sql::select($queryJoin);
            $joins     = [];
            foreach ($list['gcSelectLJoin'][$table] ?? [] as $col) {
                if (!isset($dbSchema[$firstTable][$col])) {
                    foreach ($list['gcSelectLJoin'] as $joinTable => $joinCols) {
                        if (isset($dbSchema[$joinTable][$col])) {
                            $joins[$joinTable] = $list['gcSelectLJoin'][$joinTable] ?? [];
                        }
                    }
                }
                $joins[$table] = $list['gcSelectLJoin'][$table] ?? [];
            }
            $query .= Sql::selectLeftJoinUsing($joins);

            foreach ($list['gcSelectOrderBy'] ?? [] as $orderTable => $orderCols) {
                if (isset($joins[$orderTable])) {
                    $query .= Sql::selectOrderBy([$orderTable => $orderCols]);
                }
            }

            $done       = 0;
            $askForData = true;
            do {
                $chunk = $query . PHP_EOL . 'LIMIT ' . $done . ', ' . 5000 . PHP_EOL;

                $stmt = $db->prepare($chunk);
                $stmt->execute();
                $result   = $stmt->get_result();
                $rowCount = $stmt->affected_rows;

                if ($rowCount) {
                    $done += $rowCount;
                    while ($data = $result->fetch_assoc()) {
                        // $data = array_map('strval', $data);
                        foreach ($queryJoin[$table] as $column) {
                            $items[$data[$firstColumn]][$table][$data[$table . 'Id']][$column] = $data[$column];
                        }
                    }
                } else {
                    $askForData = false;
                }

                $result->free();
                $stmt->close();

            } while ($askForData);

        }

        Director::timerStop('list ' . $table);
        $i++;
    }

    return $items;
});

// dd($items);



// get table columns to render per column

foreach ($list['gcColumns'] as $columnKey => $column)
    foreach ($column['gcColContent'] ?? [] as $colContent)
        foreach ($colContent['dbCols'] as $dbCol)
            $list['gcColumns'][$columnKey]['tablesAndCols'][$colContent['dbTab']][] = [
                'col'  => $dbCol,
                'type' => $colContent['colType'],
            ];

$columns = $list['gcColumns'];
foreach ($columns as $columnId => $column) {
    if (!$column) {
        unset($columns[$columnId]);
        continue;
    }

    if (is_array($column['label'] ?? '')) {
        $columns[$columnId]['label'] = substr($column['label'][0], 0, -3);
    }
}




// make html for all rows, using cache

$rows      = $app->cacheGet('editor', 3, 'list-' . $pgSlug . '-rows', function() use ($app, $editor, $pgSlug, $firstTable, $items, $columns) {
    $rows         = [];
    $tags         = [];
    $currentColor = 0;
    $thumbsToShow = 3;
    foreach ($items as $itemId => $item) {
        $statusClass = '';
        if (isset($item[$firstTable][$itemId][$firstTable . 'Status'])) $statusClass = ' status-' . (int)($item[$firstTable][$itemId][$firstTable . 'Status'] ?? 0);
        $ht = '<a class="row' . $statusClass . '" href="/edit/' . $pgSlug . '/' . $itemId . '">' . PHP_EOL;

        foreach ($columns as $columnId => $column) {
            if (!$column) continue;
            $ht .= '    <div class="col ' . $column['cssClass'] . '">' . PHP_EOL;
            foreach ($column['tablesAndCols'] as $dbTable => $dbColumns) {
                $countFound = false;
                $i          = 0;
                foreach ($item[$dbTable] as $data) {
                    if ($countFound) continue;
                    $tagFound = false;

                    foreach ($dbColumns as $columnData) {
                        if ($tagFound) continue;
                        $colRowItemClass = 'col-' . $columnData['type'];
                        if ($i == $thumbsToShow) $colRowItemClass .= ' more';
                        $r = '';

                        $dbColumn = $columnData['col'];
                        if (!isset($data[$dbColumn])) continue;
                        $value = $data[$dbColumn];

                        $isHomeSlug = ($pgSlug == $editor->homeSlug && $value == '' && substr($dbColumn, 0, 9) == 'pageSlug_');

                        if (count($app->langs) > 1 && substr($dbColumn, -3, 1) == '_' && in_array(substr($dbColumn, -2), $app->langs)) {
                            $r .= '<span class="input-label-lang">' . substr($dbColumn, -2) . '</span> ';
                        }


                        switch ($columnData['type']) {
                            case 'thumb':
                                if ($i > $thumbsToShow) continue 2;
                                if ($i == $thumbsToShow) {
                                    $r .= count($item[$dbTable]) . PHP_EOL;
                                } else {
                                    $img = $app->imageGet($value, ['w' => 256, 'h' => 256, 'fit' => 'cover', 'version' => 'mtime'], false);
                                    if ($img) {
                                        $r .= AppImage::render($img) . PHP_EOL;
                                    } else {
                                        $r .= '<div class="nophoto" style="background-image:url(/edit/gfx/no-photo.png);"></div>' . PHP_EOL;
                                    }
                                }
                                $colRowItemClass .= ' figure';

                                break;

                            case 'count':
                                $r          .= count($item[$dbTable]);
                                $countFound = true;
                                break;

                            case 'tag':
                                if (!$value) break;
                                $tagFound = true;
                                if (!isset($tags[$value])) $tags[$value] = $currentColor++;
                                $colRowItemClass .= ' brewer-' . Text::h(1 + ($tags[$value] % 9));
                                $r               .= Text::t($value);
                                break;

                            case 'slug':
                                $r .= '/&puncsp;' . Text::h($value);
                                break;

                            case 'timestamp':
                            case 'datetime':
                                $r .= Text::h(Text::formatDate($value, 'd MMM y H:m'));
                                break;

                            case 'date':
                                $value = strtotime($value);
                                $r     .= Text::h(Text::formatDate($value, 'd MMM y'));
                                break;

                            case 'time':
                                if (substr($value, -3) == ':00') $value = substr($value, 0, 5);
                                $r .= Text::h($value);
                                break;

                            case 'month':
                                $dt = DateTime::createFromFormat('!m', $value);
                                $r  .= Text::h(ucfirst(Text::formatDate($dt, 'MMM')));
                                break;

                            case 'small':
                                $r .= Text::h($value);
                                break;

                            default:
                                if (is_string($value)) $r .= Text::firstLine($value);
                                break;
                        }

                        if (empty($value) && $columnData['type'] != 'thumb' && !$isHomeSlug) {
                            $colRowItemClass .= ' empty';
                            $r               .= Text::t('Empty');
                        }

                        $ht .= '        <div class="' . $colRowItemClass . '">' . $r . '</div>' . PHP_EOL;
                        $i++;
                    }
                }
            }

            $ht .= '    </div>' . PHP_EOL;

        }
        $ht            .= '</a>' . PHP_EOL;
        $rows[$itemId] = $ht;
    }

    return $rows;
});
$rowsTotal = count($rows);




// integer filters (enum)

$filterInts = $list['gcFilterInts'];
foreach ($filterInts as $filterId => $filter) {
    foreach ($filter['options'] as $int => $value) {
        $filterInts[$filterId]['options'][$int]['checked'] = false;
        if (strpos($filterInts[$filterId]['options'][$int]['cssClass'], 'active') !== false) {
            $filterInts[$filterId]['options'][$int]['checked']  = true;
            $filterInts[$filterId]['options'][$int]['cssClass'] = (str_replace('active', '', $filterInts[$filterId]['options'][$int]['cssClass']));
        }
        if (empty($_POST)) continue;

        if (!isset($_POST['filterInts'][$filterId][$int])) {
            $filterInts[$filterId]['options'][$int]['checked'] = false;
        }
    }
}

$intFiltersActive = [];
foreach ($filterInts as $filterId => $filter) {
    foreach ($filter['options'] as $int => $value) {
        if (!$filterInts[$filterId]['options'][$int]['checked']) {
            $intFiltersActive[] = $filterId;
            // break 2;
        }
    }
}

Director::timerStart('Filter Ints');
foreach ($intFiltersActive as $filterId) {

    $itemsByInt = $app->cacheGet('editor', 3, 'list- ' . $pgSlug . '-filterInt-' . $filterId, function() use ($items, $filterInts, $filterId) {
        $itemsByInt = [];
        foreach ($items as $itemId => $item)
            foreach ($filterInts[$filterId]['filterWhat'] as $dbTable => $dbColumns)
                foreach ($dbColumns as $dbColumn)
                    foreach ($item[$dbTable] as $tableKeyId => $value)
                        $itemsByInt[$item[$dbTable][$tableKeyId][$dbColumn]][$itemId] = true;

        return $itemsByInt;
    });

    $filteredInts = [];
    foreach ($filterInts as $filterId => $filter) {
        if (!isset($_POST['filterInts'][$filterId])) continue;
        if (!isset($itemsByInt)) continue;
        $ints = $_POST['filterInts'][$filterId];
        krsort($ints);
        foreach ($ints as $int => $value) {
            if ($value && isset($itemsByInt[$int])) {
                $filteredInts += $itemsByInt[$int];
            }
        }
    }
    $rows = array_intersect_key($rows, $filteredInts);

}
Director::timerStop('Filter Ints');




// text filters

$filterTexts       = $list['gcFilterTexts'];
$textFiltersActive = [];
foreach ($_POST['filterTexts'] ?? [] as $filterId => $ints) {
    if (!isset($filterTexts[$filterId])) continue;
    if ($ints !== '') $textFiltersActive[] = $filterId;
}

Director::timerStart('Filter Texts');
foreach ($textFiltersActive as $filterId) {
    $filterInput = trim($_POST['filterTexts'][$filterId] ?? '', '+ ');
    if (!$filterInput) continue;
    $filterInput = explode('+', $filterInput);

    $textFilterItems = $app->cacheGet('editor', 4, 'list-' . $pgSlug . '-filterText-' . $filterId, function() use ($app, $rows, $items, $filterTexts, $filterId) {
        foreach ($rows as $itemId => $row) {
            $textFilterItems[$itemId] = '';
            $emptyFound               = false;
            foreach ($filterTexts[$filterId]['filterWhat'] as $dbTable => $dbColumns) {
                foreach ($dbColumns as $dbColumn) {
                    foreach ($items[$itemId][$dbTable] as $tableKeyId => $value) {
                        if (is_array($value)) {
                            $value = $value[$dbColumn];
                        }
                        if (empty($value)) {
                            $emptyFound = true;
                        } else if (substr($dbColumn, 0, 9) == 'timestamp') {
                            $textFilterItems[$itemId] .= Text::formatSearch(Text::formatDate($value, 'd MMM y')) . ' ';
                        } else if (substr($dbColumn, 0, 4) == 'date') {
                            $textFilterItems[$itemId] .= Text::formatSearch(Text::formatDate(strtotime($value), 'd MMM y')) . ' ';
                        } else {
                            $textFilterItems[$itemId] .= Text::formatSearch($value) . ' ';
                        }
                    }
                }
            }
            $textFilterItems[$itemId] = trim($textFilterItems[$itemId]);
            if ($emptyFound) {
                $textFilterItems[$itemId] = '{{empty}}' . $textFilterItems[$itemId];
            }
        }

        return $textFilterItems;
    });

    $itemsFiltered = [];
    foreach ($textFilterItems as $itemId => $text) {
        $filterFound = true;
        foreach ($filterInput as $word) {
            $word = Text::formatSearch($word);
            if (strpos($text, $word) === false) {
                $filterFound = false;
            }
        }
        if (!$filterFound) $itemsFiltered[$itemId] = true;
    }
    if ($itemsFiltered) $rows = array_diff_key($rows, $itemsFiltered);
}
Director::timerStop('Filter Texts');




// pagination

$pagination   = new Pagination((int)($_POST['page'] ?? 1), (int)($_POST['itemsPerPage'] ?? 50));
$rowsFiltered = count($rows);
$pagination->setItemsTotal($rowsFiltered);
$offset = $pagination->itemFirst - 1;
$length = $pagination->itemsPerPage;
if ($length >= $pagination->itemsTotal) $length = null;

$rows = array_slice($rows, $offset, $length);




// finish

$hdTitle = Text::t($geConf[$pgSlug]['gcTitlePlural']) . ' - ' . $hdTitle;
$pgTitle = Text::t($geConf[$pgSlug]['gcTitlePlural']);

