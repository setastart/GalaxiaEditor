<?php

use Galaxia\G;
use Galaxia\Pagination;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\Cache;
use GalaxiaEditor\E;


// ajax

if (G::$req->xhr) {
    G::$editor->layout = 'none';
    G::$editor->view   = 'list/results';
}




// setup list

$list        = E::$section['gcList'];
$firstTable  = key($list['gcSelect']);
$firstColumn = $list['gcSelect'][$firstTable][0];

$order = '';
if (E::$itemId ?? '') {
    $order                 = 'order-';
    $list['gcFilterInts']  = [];
    $list['gcFilterTexts'] = [];
    $_POST['itemsPerPage'] = 10000;
    G::$editor->view       = 'list/order';
}


$dbSchema = [];

$query = '
    SELECT TABLE_NAME, COLUMN_NAME, IS_NULLABLE, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLUMN_TYPE, COLUMN_KEY
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = ?
';

$stmt = G::prepare($query);
$stmt->bind_param('s', G::$app->mysqlDb);
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




// get tag colors from filters

$tags         = [];
$currentColor = 0;
foreach ($list['gcFilterInts'] as $filterId => $filter) {
    if (!isset($filter['filterType'])) continue;
    $table = array_key_first($filter['filterWhat']);
    $col   = $filter['filterWhat'][$table][0];

    switch ($filter['filterType']) {
        case 'tag':
            $tags[$table] = [];
            foreach ($filter['options'] as $val => $option) {
                $tags[$table][$col][$val] = $currentColor++;
            }
            break;
    }
}
// dd($tags);



// get items from database using cache

$items = Cache::listItems($order, function() use ($list, $firstTable, $firstColumn, $dbSchema, $order) {
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
    // $items = chunkSelectQuery($query, $f);




    // add key columns to joined tables (used to group joins in columns)
    $selectQuery = $list['gcSelect'];
    $items       = [];
    $i           = 0;
    foreach ($selectQuery as $table => $columns) {

        G::timerStart('list ' . $table);
        $keyCol = $table . 'Id';
        if (!in_array($keyCol, $columns)) array_unshift($selectQuery[$table], $keyCol);

        if ($i == 0) {

            $queryMain = [$table => $selectQuery[$table]];
            $query     = Sql::select($queryMain);


            if ($order && isset($list['gcLinks']['order']['gcSelectOrderBy'][$table])) {
                $query .= Sql::selectOrderBy([$table => $list['gcLinks']['order']['gcSelectOrderBy'][$table]]);
            } else if (isset($list['gcSelectOrderBy'][$table])) {
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

            Sql::chunkSelect($query, $f, $items);

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

            if ($order && isset($list['gcLinks']['order']['gcSelectOrderBy'])) {
                foreach ($list['gcLinks']['order']['gcSelectOrderBy'] ?? [] as $orderTable => $orderCols) {
                    if (isset($joins[$orderTable])) {
                        $query .= Sql::selectOrderBy([$orderTable => $orderCols]);
                    }
                }
            } else if (isset($list['gcSelectOrderBy'])) {
                foreach ($list['gcSelectOrderBy'] ?? [] as $orderTable => $orderCols) {
                    if (isset($joins[$orderTable])) {
                        $query .= Sql::selectOrderBy([$orderTable => $orderCols]);
                    }
                }
            }



            $done       = 0;
            $askForData = true;
            do {
                $chunk = $query . PHP_EOL . 'LIMIT ' . $done . ', ' . 5000 . PHP_EOL;

                $stmt = G::prepare($chunk);
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

        G::timerStop('list ' . $table);
        $i++;
    }

    return $items;
});

// dd($items);



// get table columns to render per column

foreach ($list['gcColumns'] as $columnKey => $column) {
    foreach ($column['gcColContent'] ?? [] as $colContent) {
        foreach ($colContent['dbCols'] as $dbCol) {
            $list['gcColumns'][$columnKey]['tablesAndCols'][$colContent['dbTab']][] = [
                'col'    => $dbCol,
                'type'   => $colContent['colType'],
                'parent' => $colContent['gcParent'] ?? '',
            ];
        }
    }
}

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

$rows = Cache::listRows($order, function() use ($firstTable, $items, $columns, $tags, $order) {
    $rows         = [];
    $currentColor = 0;
    $thumbsToShow = 3;
    foreach ($items as $itemId => $item) {
        $statusClass = '';
        if (isset($item[$firstTable][$itemId][$firstTable . 'Status'])) $statusClass = ' status-' . (int)($item[$firstTable][$itemId][$firstTable . 'Status'] ?? 0);

        if ($order) {
            $ht = '<div id="order-' . $itemId . '" class="row' . $statusClass . '">' . PHP_EOL;
        } else {
            $ht = '<a class="row' . $statusClass . '" href="/edit/' . E::$pgSlug . '/' . $itemId . '">' . PHP_EOL;
        }

        foreach ($columns as $columnId => $column) {
            if (!$column) continue;
            $ht .= '    <div class="col ' . $column['cssClass'] . '">' . PHP_EOL;
            foreach ($column['tablesAndCols'] as $dbTable => $dbColumns) {
                $countFound = false;
                $i          = 0;
                foreach ($item[$dbTable] as $itemId2 => $data) {
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

                        $isHomeSlug = (E::$pgSlug == G::$editor->homeSlug && $value == '' && substr($dbColumn, 0, 9) == 'pageSlug_');

                        if (count(G::langs()) > 1 && substr($dbColumn, -3, 1) == '_' && in_array(substr($dbColumn, -2), G::langs())) {
                            $r .= '<span class="input-label-lang">' . substr($dbColumn, -2) . '</span> ';
                        }


                        if ($columnData['parent']) {
                            $value = '';
                            foreach ($columnData['parent'] as $parent) {
                                // geD($parent);
                                $text = $item[$firstTable][$itemId][$parent] ?? false;
                                if (!$text) continue;

                                $value .= Text::t($text) . ' / ';
                                // if ($value) break;
                            }
                            $value = rtrim($value, ' / ');
                        }


                        switch ($columnData['type']) {
                            case 'thumb':
                                if ($i > $thumbsToShow) continue 2;
                                if ($i == $thumbsToShow) {
                                    $r .= count($item[$dbTable]) . PHP_EOL;
                                } else {
                                    $img = G::imageGet($value, ['w' => 256, 'h' => 256, 'version' => 'mtime', 'loading' => false], false);
                                    if ($img) {
                                        $r .= G::image($img) . PHP_EOL;
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
                                if (!isset($tags[$dbTable][$dbColumn][$value])) $tags[$dbTable][$dbColumn][$value] = $currentColor++;
                                $colRowItemClass .= ' brewer-' . Text::h(1 + ($tags[$dbTable][$dbColumn][$value] % 9));
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

                            case 'position':
                            case 'small':
                                $r .= Text::h($value);
                                break;

                            default:
                                if (is_string($value)) $r .= Text::firstLine($value);
                                if (is_int($value)) $r .= $value;
                                break;
                        }

                        if ($value == '' && $columnData['type'] != 'thumb' && !$isHomeSlug) {
                            $colRowItemClass .= ' empty';
                            $r               .= $value . Text::t('Empty');
                        }

                        $ht .= '        <div class="' . $colRowItemClass . '">' . $r . '</div>' . PHP_EOL;
                        $i++;
                    }
                }
            }

            $ht .= '    </div>' . PHP_EOL;

        }
        if ($order) {
            ob_start();

// @formatter:off ?>
            <div class="btn-row pad">
                <div class="btn-group">
                    <button title="<?=Text::t("First")?>" type="button" class="ev-module-first btn-new reorder-first active" data-target="order-<?=$itemId?>"></button>
                    <button title="<?=Text::t("Previous")?>" type="button" class="ev-module-up btn-new reorder-prev active" data-target="order-<?=$itemId?>"></button>
                </div>
                <div class="btn-group">
                    <input class="module-position input-text" type="text" min="1" name="order[<?=$itemId?>]" value="<?=$item[$firstTable][$itemId]['position'] ?? '?'?>">
                    <button type="button" class="ev-module-go btn-new active" data-target="order-<?=$itemId?>">go!</button>
                </div>
                <div class="btn-group">
                    <button title="<?=Text::t("Next")?>" type="button" class="ev-module-down btn-new reorder-next active" data-target="order-<?=$itemId?>"></button>
                    <button title="<?=Text::t("Last")?>" type="button" class="ev-module-last btn-new reorder-last active" data-target="order-<?=$itemId?>"></button>
                </div>
            </div>
<?php // @formatter:on
            $ht .= ob_get_clean();
        }

        if ($order) {
            $ht .= '</div>' . PHP_EOL;
        } else {
            $ht .= '</a>' . PHP_EOL;
        }

        $rows[$itemId] = $ht;
    }

    return $rows;
});

$rowsTotal = count($rows);




// integer filters (enum)

$filterInts = $list['gcFilterInts'];
foreach ($filterInts as $filterId => $filter) {
    if (isset($filter['filterType'])) {
        $table = array_key_first($filter['filterWhat']);
        $col   = $filter['filterWhat'][$table][0];

        switch ($filter['filterType']) {
            case 'tag':
                foreach ($filter['options'] as $val => $option) {
                    if (!isset($tags[$table][$col][$val])) continue;
                    $filterInts[$filterId]['options'][$val]['cssClass'] .= ' brewer-' . Text::h(1 + ($tags[$table][$col][$val] % 9));
                }
                break;
        }
    }
    foreach ($filter['options'] as $int => $value) {

        $filterInts[$filterId]['options'][$int]['checked'] = false;
        if (str_contains($filterInts[$filterId]['options'][$int]['cssClass'] ?? '', 'active')) {
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

G::timerStart('Filter Ints');
foreach ($intFiltersActive as $filterId) {

    $itemsByInt = Cache::listItemsFilterInt($filterId, function() use ($items, $filterInts, $filterId) {
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
G::timerStop('Filter Ints');




// text filters

$filterTexts       = $list['gcFilterTexts'];
$textFiltersActive = [];
foreach ($_POST['filterTexts'] ?? [] as $filterId => $ints) {
    if (!isset($filterTexts[$filterId])) continue;
    if ($ints !== '') $textFiltersActive[] = $filterId;
}

G::timerStart('Filter Texts');
foreach ($textFiltersActive as $filterId) {
    $filterInput = trim($_POST['filterTexts'][$filterId] ?? '', '+ ');
    if (!$filterInput) continue;
    $filterInput = explode('+', $filterInput);

    $textFilterItems = Cache::listItemsFilterText($filterId, function() use ($rows, $items, $filterTexts, $filterId) {
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
            if (!str_contains($text, $word)) {
                $filterFound = false;
            }
        }
        if (!$filterFound) $itemsFiltered[$itemId] = true;
    }
    if ($itemsFiltered) $rows = array_diff_key($rows, $itemsFiltered);
}
G::timerStop('Filter Texts');




// pagination

$pagination   = new Pagination((int)($_POST['page'] ?? 1), (int)($_POST['itemsPerPage'] ?? 50));
$rowsFiltered = count($rows);
$pagination->setItemsTotal($rowsFiltered);
$offset = $pagination->itemFirst - 1;
$length = $pagination->itemsPerPage;
if ($length >= $pagination->itemsTotal) $length = null;

$rows = array_slice($rows, $offset, $length, true);




// finish

E::$hdTitle = Text::t(E::$section['gcTitlePlural']) . ' - ' . E::$hdTitle;
E::$pgTitle = Text::t(E::$section['gcTitlePlural']);

if ($order) {
    E::$hdTitle = sprintf(Text::t('Order %s'), E::$hdTitle);
    E::$pgTitle = sprintf(Text::t('Order %s'), E::$pgTitle);
}
