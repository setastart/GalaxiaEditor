<?php

use Galaxia\{Director, Pagination};


// ajax

if (Director::$ajax) {
    $editor->layout = 'none';
    $editor->view = 'list/results';
}




// setup list

$list = $geConf[$pgSlug]['gcList'];
$firstTable = key($list['gcSelect']);
$firstColumn = $list['gcSelect'][$firstTable][0];



// get items from database using cache

$items = $app->cacheGet('editor', 2, 'list', $pgSlug, 'items', function() use ($db, $list, $firstColumn) {
    $items = [];
    // add key columns to joined tables (used to group joins in columns)
    $selectQueryWithJoinKeys = $list['gcSelect'];
    foreach ($selectQueryWithJoinKeys as $table => $columns) {
        $keyCol = $table . 'Id';
        if (!in_array($keyCol, $columns)) array_unshift($selectQueryWithJoinKeys[$table], $keyCol);
    }

    $query = querySelect($selectQueryWithJoinKeys);
    $query .= querySelectLeftJoinUsing($list['gcSelectLJoin'] ?? []);
    $query .= querySelectOrderBy($list['gcSelectOrderBy'] ?? []);

    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($data = $result->fetch_assoc()) {
        $data = array_map('strval', $data);
        foreach($list['gcSelect'] as $table => $columns) {
            foreach ($columns as $column) {
                $items[$data[$firstColumn]][$table][$data[$table . 'Id']][$column] = $data[$column];
            }
        }
    }
    $stmt->close();

    return $items;
});



// get table columns to render per column

foreach ($list['gcColumns'] as $columnKey => $column)
    foreach ($column['gcColContent'] ?? [] as $colContent)
        foreach ($colContent['dbCols'] as $dbCol)
            $list['gcColumns'][$columnKey]['tablesAndCols'][$colContent['dbTab']][] = [
                'col' => $dbCol,
                'type' => $colContent['colType'],
            ];

$columns = $list['gcColumns'];
foreach ($columns as $columnId => $column) {
    if (!$column) continue;

    if (is_array($column['label'] ?? '')) {
        $columns[$columnId]['label'] = substr($column['label'][0], 0, -2);
    }
}



// make html for all rows, using cache

$rows = $app->cacheGet('editor', 3, 'list', $pgSlug, 'rows', function() use ($app, $pgSlug, $firstTable, $items, $columns) {
    $rows = [];
    $tags = [];
    $currentColor = 0;
    foreach ($items as $itemId => $item) {
        $statusClass = '';
        if (isset($item[$firstTable][$itemId][$firstTable . 'Status'])) $statusClass = ' status-' . (int)($item[$firstTable][$itemId][$firstTable . 'Status'] ?? 0);
$ht = '<a class="row' . $statusClass . '" href="/edit/' . $pgSlug . '/' . $itemId . '">' . PHP_EOL;

        foreach ($columns as $columnId => $column) {
            if (!$column) continue;
$ht .= '    <div class="col ' . $column['cssClass'] . '">' . PHP_EOL;
            foreach ($column['tablesAndCols'] as $dbTable => $dbColumns) {
                $countFound = false;
                    $i = 0;
                foreach ($item[$dbTable] as $data) {
                    if ($countFound) continue;
                    $tagFound = false;

                    foreach ($dbColumns as $columnData) {
                        if ($tagFound) continue;
                        $colRowItemClass = 'col-' . $columnData['type'];
                        if ($i == 5) $colRowItemClass .= ' more';
                        $r = '';

                        $dbColumn = $columnData['col'];
                        if (!isset($data[$dbColumn])) continue;
                        $value = $data[$dbColumn];


                        if (count($app->langs) > 1 && substr($dbColumn, -3, 1) == '_' && in_array(substr($dbColumn, -2), $app->langs)) {
                            $r .= '<span class="input-label-lang">' . substr($dbColumn, -2) . '</span> ';
                        }


                        switch ($columnData['type']) {
                            case 'thumb':
                                if ($i > 5) continue 2;
                                if ($i == 5) {
                                    $r .= '&#xff0b;&#xfe0e;' . PHP_EOL;
                                } else {
                                    $img = $app->imageGet($value, ['w' => 256, 'h' => 256, 'fit' => 'cover', 'version' => 'mtime'], false);
                                    if ($img) {
                                        $r .= gImageRender($img) . PHP_EOL;
                                    } else {
                                        $r .= '<div class="nophoto" style="background-image:url(/edit/gfx/no-photo.png);"></div>' . PHP_EOL;
                                    }
                                }
                                $colRowItemClass .= ' figure';

                                break;

                            case 'count':
                                $r .= count($item[$dbTable]);
                                $countFound = true;
                                break;

                            case 'tag':
                                if (!$value) break;
                                $tagFound = true;
                                if (!isset($tags[$value])) $tags[$value] = $currentColor++;
                                $colRowItemClass .= ' brewer-' . h(1 + ($tags[$value] % 9));
                                $r .= t($value);
                                break;

                            case 'slug':
                                $r .= '/&puncsp;' . h($value);
                                break;

                            case 'timestamp':
                            case 'datetime':
                                $r .= h(gFormatDate($value, 'd MMM y H:m'));
                                break;

                            case 'date':
                                $value = strtotime($value);
                                $r .= h(gFormatDate($value, 'd MMM y'));
                                break;

                            case 'time':
                                if (substr($value, -3) == ':00') $value = substr($value, 0, 5);
                                $r .= h($value);
                                break;

                            case 'month':
                                $dt = DateTime::createFromFormat('!m', $value);
                                $r .= h(ucfirst(gFormatDate($dt, 'MMM')));
                                break;

                            case 'small':
                                $r .= h($value);
                                break;

                            default:
                                if (is_string($value)) $r .= firstLine($value);
                                break;
                        }

                        if (empty($value) && $columnData['type'] != 'thumb') {
                            $colRowItemClass .= ' empty';
                            $r .= t('Empty');
                        }

$ht .= '        <div class="' . $colRowItemClass . '">' . $r . '</div>' . PHP_EOL;
                        $i++;
                    }
                }
            }

$ht .= '    </div>' . PHP_EOL;

        }
$ht .= '</a>' . PHP_EOL;
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
            $filterInts[$filterId]['options'][$int]['checked'] = true;
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

    $itemsByInt = $app->cacheGet('editor', 3, 'list', $pgSlug, 'filterInt-' . $filterId, function() use ($items, $filterInts, $filterId) {
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
                $filteredInts = $filteredInts + $itemsByInt[$int];
            }
        }
    }
    $rows = array_intersect_key($rows, $filteredInts);

}
Director::timerStop('Filter Ints');




// text filters

$filterTexts = $list['gcFilterTexts'];
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

    $textFilterItems = $app->cacheGet('editor', 4, 'list', $pgSlug, 'filterText-' . $filterId, function() use ($app, $rows, $items, $filterTexts, $filterId) {
        foreach ($rows as $itemId => $row) {
            $textFilterItems[$itemId] = '';
            $emptyFound = false;
            foreach ($filterTexts[$filterId]['filterWhat'] as $dbTable => $dbColumns) {
                foreach ($dbColumns as $dbColumn) {
                    foreach ($items[$itemId][$dbTable] as $tableKeyId => $value) {
                        if (is_array($value)) {
                            $value = $value[$dbColumn];
                        }
                        if (empty($value)) {
                            $emptyFound = true;
                        } else if (substr($dbColumn, 0, 9) == 'timestamp') {
                            $textFilterItems[$itemId] .= gPrepareTextForSearch(gFormatDate($value, 'd MMM y')) . ' ';
                        } else if (substr($dbColumn, 0, 4) == 'date') {
                            $textFilterItems[$itemId] .= gPrepareTextForSearch(gFormatDate(strtotime($value), 'd MMM y')) . ' ';
                        } else {
                            $textFilterItems[$itemId] .= gPrepareTextForSearch($value) . ' ';
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
            $word = gPrepareTextForSearch($word);
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

$pagination = new Pagination((int) ($_POST['page'] ?? 1), (int) ($_POST['itemsPerPage'] ?? 50));
$rowsFiltered = count($rows);
$pagination->setItemsTotal($rowsFiltered);
$offset = $pagination->itemFirst - 1;
$length = $pagination->itemsPerPage;
if ($length >= $pagination->itemsTotal) $length = null;

$rows = array_slice($rows, $offset, $length);




// finish

$hdTitle = t($geConf[$pgSlug]['gcTitlePlural']) . ' - ' . $hdTitle;
$pgTitle = t($geConf[$pgSlug]['gcTitlePlural']);

