<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\AppImage;
use Galaxia\AppTimer;
use Galaxia\G;
use Galaxia\Pagination;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\Cache;
use GalaxiaEditor\E;


// ajax

if (G::$req->xhr) {
    G::$editor->layout = 'layout-none';
    G::$editor->view   = 'list/results';
}




// setup list

$list        = E::$section['gcList'];
$firstTable  = key($list['gcSelect']);
$firstColumn = $list['gcSelect'][$firstTable][0];

if (E::$itemId ?? '') {
    E::$listOrder          = 'order-';
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
            foreach ($filter['options'] ?? [] as $val => $option) {
                $tags[$table][$col][$val] = $currentColor++;
            }
            break;
    }
}
// dd($tags);



// get items from database using cache

$items = Cache::listItems(E::$listOrder, function() use ($list, $firstTable, $firstColumn, $dbSchema) {
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

        AppTimer::start('list ' . $table);
        $keyCol = $table . 'Id';
        if (!in_array($keyCol, $columns)) array_unshift($selectQuery[$table], $keyCol);

        if ($i == 0) {

            $queryMain = [$table => $selectQuery[$table]];
            $query     = Sql::select($queryMain);


            if (E::$listOrder && isset($list['gcLinks']['order']['gcSelectOrderBy'][$table])) {
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

            $orders = [];
            if (E::$listOrder && isset($list['gcLinks']['order']['gcSelectOrderBy'])) {
                foreach ($list['gcLinks']['order']['gcSelectOrderBy'] ?? [] as $orderTable => $orderCols) {
                    if (isset($joins[$orderTable])) {
                        $orders[$orderTable] = $orderCols;
                    }
                }
            } else if (isset($list['gcSelectOrderBy'])) {
                foreach ($list['gcSelectOrderBy'] ?? [] as $orderTable => $orderCols) {
                    if (isset($joins[$orderTable])) {
                        $orders[$orderTable] = $orderCols;
                    }
                }
            }
            $query .= Sql::selectOrderBy($orders);


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

        AppTimer::stop('list ' . $table);
        $i++;
    }

    return $items;
});

// geD($items);



// get table columns to render per column

foreach ($list['gcColumns'] as $columnKey => $column) {
    foreach ($column['gcColContent'] ?? [] as $colContent) {
        foreach ($colContent['dbCols'] as $dbCol) {
            $list['gcColumns'][$columnKey]['tablesAndCols'][$colContent['dbTab']][] = [
                'col'    => $dbCol,
                'type'   => $colContent['colType'],
                'parent' => $colContent['gcParent'] ?? '',
                'other'  => $colContent['gcOther'] ?? '',
            ];
        }
    }
}

E::$listColumns = $list['gcColumns'];
foreach (E::$listColumns as $columnId => $column) {
    if (!$column) {
        unset(E::$listColumns[$columnId]);
        continue;
    }

    if (is_array($column['label'] ?? '')) {
        E::$listColumns[$columnId]['label'] = substr($column['label'][0], 0, -3);
    }
}



function formatField(string $value, string $type): string {
    switch ($type) {
        case 'slug':
            return '/&puncsp;' . Text::h($value);

        case 'timestamp':
        case 'datetime':
            return Text::h(Text::formatDate($value, 'd MMM y H:mm'));

        case 'date':
            return Text::h(Text::formatDate(strtotime($value), 'd MMM y'));

        case 'time':
            if (str_ends_with($value, ':00')) $value = substr($value, 0, 5);
            return Text::h($value);

        case 'month':
            $dt = DateTime::createFromFormat('!m', $value);
            return Text::h(ucfirst(Text::formatDate($dt, 'MMM')));

        default:
            return Text::h($value);
    }
}

// make html for all rows, using cache

E::$listRows = Cache::listRows(E::$listOrder, function() use ($firstTable, $items, $tags) {
    $rows         = [];
    $currentColor = 0;
    $thumbsToShow = 3;
    foreach ($items as $itemId => $item) {
        $statusClass = '';
        if (isset($item[$firstTable][$itemId][$firstTable . 'Status'])) $statusClass = ' status-' . (int)($item[$firstTable][$itemId][$firstTable . 'Status'] ?? 0);

        if (E::$listOrder) {
            $ht = '<div id="order-' . $itemId . '" class="row' . $statusClass . '">' . PHP_EOL;
        } else {
            $ht = '<a class="row' . $statusClass . '" href="/edit/' . E::$pgSlug . '/' . $itemId . '">' . PHP_EOL;
        }

        foreach (E::$listColumns as $column) {
            if (!$column) continue;
            $ht .= '    <div class="col ' . $column['cssClass'] . '">' . PHP_EOL;
            $i  = 0;

            $thumbCount = 0;
            foreach ($column['tablesAndCols'] as $dbTable => $dbColumns) {
                foreach ($dbColumns as $columnData) {
                    if ($columnData['type'] != 'thumb') continue;
                    foreach ($item[$dbTable] as $data) {
                        if ($data[$columnData['col']]) $thumbCount++;
                    }
                }
            }

            foreach ($column['tablesAndCols'] as $dbTable => $dbColumns) {
                $countFound = false;

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

                        $isHomeSlug = (E::$pgSlug == G::$editor->homeSlug && $value == '' && str_starts_with($dbColumn, 'pageSlug_'));

                        $langLabel = '';
                        if (count(G::langs()) > 1 && substr($dbColumn, -3, 1) == '_' && in_array(substr($dbColumn, -2), G::langs())) {
                            $langLabel = '<span class="input-label-lang">' . substr($dbColumn, -2) . '</span> ';
                        }


                        if ($columnData['parent']) {

                            $value = '';
                            foreach ($columnData['parent'] as $parent) {
                                $text = $item[$firstTable][$itemId][$parent] ?? false;
                                if (!$text) continue;

                                $value .= Text::t($text) . ' / ';
                            }
                            $value = rtrim($value, ' /');

                        } else if ($columnData['other']) {

                            $value = '';
                            foreach ($columnData['other'] as $otherTable => $others) {
                                foreach ($others as $otherKey => $otherVal) {
                                    if (!is_array($otherVal)) $otherVal = [$otherVal];
                                    foreach ($otherVal as $otherFieldKey => $otherFieldVal) {
                                        $type = $columnData['type'];
                                        if (is_int($otherFieldKey)) {
                                            $field = $otherFieldVal;
                                        } else {
                                            $type = $otherFieldVal;
                                            $field = $otherFieldKey;
                                        }
                                        if (substr($field, -3, 1) == '_' && substr($field, -2) != G::lang()) continue;
                                        $text = $item[$otherTable][$data[$otherKey]][$field] ?? false;
                                        if (!$text) continue;
                                        $value .= Text::t(formatField($text, $type)) . ' / ';
                                    }
                                }
                            }
                            $value = rtrim($value, ' /');

                        }


                        switch ($columnData['type']) {
                            case 'thumb':
                                if ($i > $thumbsToShow) continue 2;
                                if (!$value) continue 2;
                                if ($i == $thumbsToShow) {
                                    $r .= $thumbCount . PHP_EOL;
                                } else {
                                    $img = AppImage::imageGet($value, ['w' => 256, 'h' => 256, 'version' => 'mtime', 'loading' => false], false);
                                    if ($img) {
                                        $r .= AppImage::render($img) . PHP_EOL;
                                    } else {
                                        $r .= '<div class="nophoto" style="background-image:url(/edit/gfx/btn/no-photo.png);"></div>' . PHP_EOL;
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
                                $langLabel       = '';
                                break;

                            case 'color':
                                if (!$value) break;
                                $tagFound = true;
                                if (!isset($tags[$dbTable][$dbColumn][$value])) $tags[$dbTable][$dbColumn][$value] = $currentColor++;
                                $colRowItemClass .= ' brewer-dark-' . Text::h(1 + ($tags[$dbTable][$dbColumn][$value] % 9));
                                $r               .= Text::t($value);
                                $langLabel       = '';
                                break;

                            case 'slug':
                                $r .= '/&puncsp;' . Text::h($value);
                                break;

                            case 'timestamp':
                            case 'datetime':
                                $r .= Text::h(Text::formatDate($value, 'd MMM y H:mm'));
                                break;

                            case 'date':
                                $value = strtotime($value);
                                $r     .= Text::h(Text::formatDate($value, 'd MMM y'));
                                break;

                            case 'time':
                                if (str_ends_with($value, ':00')) $value = substr($value, 0, 5);
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

                            case 'html':
                                $r .= Text::html($value);
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

                        $ht .= '        <div class="' . $colRowItemClass . '">' . $langLabel . $r . '</div>' . PHP_EOL;
                        $i++;
                    }
                }
            }

            $ht .= '    </div>' . PHP_EOL;

        }
        if (E::$listOrder) {
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

        if (E::$listOrder) {
            $ht .= '</div>' . PHP_EOL;
        } else {
            $ht .= '</a>' . PHP_EOL;
        }

        $rows[$itemId] = $ht;
    }

    return $rows;
});

$rowsTotal = count(E::$listRows);




// integer filters (enum)

$filterIntsActive = [];
$filterInts = $list['gcFilterInts'];
foreach ($filterInts as $filterId => $filter) {
    if (isset($filter['filterType'])) {
        $table = array_key_first($filter['filterWhat']);
        $col   = $filter['filterWhat'][$table][0];

        switch ($filter['filterType']) {
            case 'tag':
                foreach ($filter['options'] ?? [] as $val => $option) {
                    if (!isset($tags[$table][$col][$val])) continue;
                    $filterInts[$filterId]['options'][$val]['cssClass'] .= ' brewer-' . Text::h(1 + ($tags[$table][$col][$val] % 9));
                }
                break;
        }
    }
    foreach ($filter['options'] ?? [] as $int => $value) {

        $filterInts[$filterId]['options'][$int]['checked'] = false;
        if (str_contains($filterInts[$filterId]['options'][$int]['cssClass'] ?? '', 'active')) {
            $filterInts[$filterId]['options'][$int]['checked']  = true;
            $filterInts[$filterId]['options'][$int]['cssClass'] = (str_replace('active', '', $filterInts[$filterId]['options'][$int]['cssClass']));
            $filterIntsActive[$filterId][$int] = '1';
        }
        if (empty($_POST)) continue;

        if (!isset($_POST['filterInts'][$filterId][$int])) {
            $filterInts[$filterId]['options'][$int]['checked'] = false;
        }
    }
}

$intFiltersActive = [];
foreach ($filterInts as $filterId => $filter) {
    foreach ($filter['options'] ?? [] as $int => $value) {
        if (!$filter['options'][$int]['checked']) {
            $intFiltersActive[$filterId] = true;
        }
    }
}

AppTimer::start('Filter Ints');
foreach ($intFiltersActive as $filterId => $_) {

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
    foreach ($filterInts as $filterIntId => $filter) {
        if (!isset($itemsByInt)) continue;
        $ints = $_POST['filterInts'][$filterIntId] ?? $filterIntsActive[$filterIntId];
        krsort($ints);
        foreach ($ints as $int => $value) {
            if ($value && isset($itemsByInt[$int])) {
                $filteredInts += $itemsByInt[$int];
            }
        }
    }
    E::$listRows = array_intersect_key(E::$listRows, $filteredInts);

}
AppTimer::stop('Filter Ints');




// text filters

$filterTexts       = $list['gcFilterTexts'];
$textFiltersActive = [];
foreach ($_POST['filterTexts'] ?? [] as $filterId => $ints) {
    if (!isset($filterTexts[$filterId])) continue;
    if ($ints !== '') $textFiltersActive[] = $filterId;
}

AppTimer::start('Filter Texts');
foreach ($textFiltersActive as $filterId) {
    $filterInput = trim($_POST['filterTexts'][$filterId] ?? '', '+ ');
    if (!$filterInput) continue;
    $filterInput = explode('+', $filterInput);

    $textFilterItems = Cache::listItemsFilterText($filterId, function() use ($items, $filterTexts, $filterId) {
        $textFilterItems = [];
        foreach (E::$listRows as $itemId => $row) {
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
                        } else if (str_starts_with($dbColumn, 'timestamp')) {
                            $textFilterItems[$itemId] .= Text::formatSearch(Text::formatDate($value, 'd MMM y')) . ' ';
                        } else if (str_starts_with($dbColumn, 'date')) {
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
    if ($itemsFiltered) E::$listRows = array_diff_key(E::$listRows, $itemsFiltered);
}
AppTimer::stop('Filter Texts');




// pagination

E::$pagination = new Pagination($_POST['page'] ?? 1, $_POST['itemsPerPage'] ?? 50);
E::$pagination->setItemCounts(count(E::$listRows), $rowsTotal);

E::$listRows = E::$pagination->sliceRows(E::$listRows);




// finish

E::$hdTitle = Text::t(E::$section['gcTitlePlural']) . ' - ' . E::$hdTitle;
E::$pgTitle = Text::t(E::$section['gcTitlePlural']);

if (E::$listOrder) {
    E::$hdTitle = sprintf(Text::t('Order %s'), E::$hdTitle);
    E::$pgTitle = sprintf(Text::t('Order %s'), E::$pgTitle);
}
