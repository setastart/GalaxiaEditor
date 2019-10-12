<?php

use Galaxia\{Director, Pagination};


// ajax

if (Director::$ajax) {
    $editor->layout = 'none';
    $editor->view = 'imageList/results';
    if (($_POST['imageListType'] ?? '') == 'image-select') $editor->view = 'imageList/selectResults';
}




// get images using cache

$items = $app->cacheGet('editor', 2, 'imageList', $pgSlug, 'items', function() use ($app) {
    $items = [];
    $imageList = gImageList($app->dirImages);

    foreach ($imageList as $imgSlug => $mtime) {
        if (!$img = $app->imageGet($imgSlug, ['w' => 256, 'h' => 256, 'fit' => 'cover', 'extra' => ['type'], 'version' => 'mtime', 'fileSize' => true], false)) continue;
        $items[$imgSlug] = $img;
    }

    uasort($items, function($a, $b) {
        return $b['mtime'] <=> $a['mtime'];
    });


    return $items;
});




// get in use items using cache

$inUse = $app->cacheGet('editor', 2, 'imageList', $pgSlug, 'inUse', function() use ($db, $geConf, $pgSlug) {
    $inUse = [];
    foreach ($geConf[$pgSlug]['gcImagesInUse'] as $gcImageInUse) {

        if (empty($gcImageInUse['gcSelect'])) return;
        $firstTable = key($gcImageInUse['gcSelect']);
        $firstColumn = $gcImageInUse['gcSelect'][$firstTable][0] ?? [];
        $query = querySelect($gcImageInUse['gcSelect']);
        $query .= querySelectLeftJoinUsing($gcImageInUse['gcSelectLJoin'] ?? []);
        $query .= querySelectOrderBy($gcImageInUse['gcSelectOrderBy'] ?? []);

        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($data = $result->fetch_assoc()) {
            $data = array_map('strval', $data);

            foreach($gcImageInUse['gcSelect'] as $table => $columns) {
                if ($table == $firstTable) {
                    foreach ($columns as $column) {
                        if ($column == $firstColumn) continue;

                        if (empty($data[$column]))
                            $data[$column] = '<span class="small red">' . t('Empty') . '</span>';

                        $inUse[$data[$firstColumn]][$table] = $data[$column];
                    }
                } else {
                    foreach ($columns as $column) {
                        if (isset($inUse[$data[$firstColumn]][$table]))
                            if (isset($inUse[$data[$firstColumn]][$table]))
                                if (in_array($data[$column], $inUse[$data[$firstColumn]][$table])) continue;

                        if (empty($data[$column]))
                            $data[$column] = '<span class="small red">' . t('Empty') . '</span>';

                        $inUse[$data[$firstColumn]][$table][] = $data[$column];
                    }
                }
            }
        }
        $stmt->close();
    }
    return $inUse;
});




// make html for all rows, using cache

switch ($_POST['imageListType'] ?? '') {
    case 'image-select':
        $rows = $app->cacheGet('editor', 3, 'imageList', $pgSlug, 'rows-select', function() use ($app, $geConf, $pgSlug, $items, $inUse) {
        $rows = [];

            foreach ($items as $imgSlug => $img) {
$html = '';
$html .= '<button type="button" id="' . h($imgSlug) . '" class="imageSelectItem" onclick="gjImageSelectorActivate(this)">' . PHP_EOL;
$html .= '    <div class="ratio">' . PHP_EOL;
$html .= '        ' . gImageRenderReflowSpacer($img['w'], $img['h']) . PHP_EOL;
$html .= '        ' . gImageRender($img, 'onerror="gjImageResizeRequest(this, event)"') . PHP_EOL;
$html .= '    </div>' . PHP_EOL;
$html .= '    <p>' . h($imgSlug) . '</p>' . PHP_EOL;
$html .= '</button>' . PHP_EOL;
                $rows[$imgSlug] = $html;
            }
            return $rows;
        });
        break;

    default:
        $rows = $app->cacheGet('editor', 3, 'imageList', $pgSlug, 'rows', function() use ($geConf, $pgSlug, $items, $inUse) {
            $rows = [];
            $imgTypes = [];
            $currentColor = 0;

            foreach ($items as $imgSlug => $img) {
$html = '';
                if (isset($img['extra']['type']))
                    if (!isset($imgTypes[$img['extra']['type']])) $imgTypes[$img['extra']['type']] = $currentColor++;
$html .= '<a class="row row-image" href="/edit/images/' . $imgSlug . '">' . PHP_EOL;
$html .= '    <div class="col flexT">' . PHP_EOL;
$html .= '        <div class="col-thumb ratio">' . PHP_EOL;
$html .= '            ' . gImageRenderReflowSpacer($img['w'], $img['h']) . PHP_EOL;
$html .= '            ' . gImageRender($img, 'onerror="gjImageResizeRequest(this, event)"') . PHP_EOL;
$html .= '        </div>' . PHP_EOL;
$html .= '    </div>' . PHP_EOL;
$html .= '    <div class="col flex1">' . PHP_EOL;
$html .= '        <div><span class="input-label-lang">' . t('Slug') . ':</span> ' . h($imgSlug) . '</div>' . PHP_EOL;
$html .= '        <div><span class="input-label-lang">' . t('Dimensions') . ':</span> <small class="grey">' . h($img['wOriginal'] . 'x' . $img['hOriginal']) . '</small></div>' . PHP_EOL;
$html .= '        <div><span class="input-label-lang">' . t('Size') . ':</span> <small class="grey">' . h(number_format($img['fileSize'], 0, '', ',') . ' B') . '</small></div>' . PHP_EOL;
$html .= '        <div><span class="input-label-lang">' . t('Created') . ':</span> <small class="grey">' . gFormatDate($img['mtime'], 'd MMM y - HH:mm') . '</small></div>' . PHP_EOL;
$html .= '    </div>' . PHP_EOL;
$html .= '    <div class="col flex1">' . PHP_EOL;
                foreach ($img['alt'] as $lang => $alt) {
                    if (empty($alt)) {
$html .= '        <div><span class="small red">' . t('Empty') . '</span></div>' . PHP_EOL;
                    } else {
$html .= '        <div><span class="input-label-lang">' . $lang . ':</span> ' . h($alt) . '</div>' . PHP_EOL;
                    }
                }
$html .= '    </div>' . PHP_EOL;
$html .= '    <div class="col flex1">' . PHP_EOL;
                if (isset($inUse[$imgSlug])) {
                    foreach ($inUse[$imgSlug] as $itemKey => $item) {
                        foreach ($item as $itemVal) {
$html .= '        <div>' . h($itemKey . '/') . '' . h($itemVal) . '</div>' . PHP_EOL;
                        }
                    }
                } else {
$html .= '        <div><span class="small red">' . t('Empty') . '</span></div>' . PHP_EOL;
                }
$html .= '    </div>' . PHP_EOL;
                if (!empty($geConf[$pgSlug]['gcImageTypes'])) {
$html .= '    <div class="col flexD tags">' . PHP_EOL;
                    if (isset($img['extra']['type'])) {
$html .= '        <div class="col-tags brewer-' . h(1 + ($imgTypes[$img['extra']['type']] % 9)) . '">' . h(t($img['extra']['type'])) . '</div>' . PHP_EOL;
                    } else {
$html .= '        <span class="small red">' . t('Empty') . '</span>' . PHP_EOL;
                    }
$html .= '    </div>' . PHP_EOL;
                }
$html .= '</a>';
                $rows[$imgSlug] = $html;
            }
            return $rows;
        });
        break;
}
$rowsTotal = count($rows);




// text filters using a file cache for each filterId

$filterTexts = [
    'slug' => [
        'label'       => 'Filter Slugs',
        'filterEmpty' => false,
    ],
    'alt' => [
        'label'       => 'Filter Alt',
        'filterEmpty' => true,
    ],
    'inuse' => [
        'label'       => 'Filter In Use',
        'filterEmpty' => true,
    ],
    'type' => [
        'label'       => 'Filter Types',
        'filterEmpty' => true,
    ],
];
$textFiltersActive = [];
foreach ($_POST['filterTexts'] ?? [] as $filterId => $ints) {
    if (!in_array($filterId, ['slug', 'alt', 'inuse', 'type'])) continue;
    if (!empty($ints)) {
        $textFiltersActive[] = $filterId;
    }
}

if ($textFiltersActive) {
    $itemsFiltered = [];

    foreach ($textFiltersActive as $filterId) {
        $filterInput = trim($_POST['filterTexts'][$filterId] ?? '', '+ ');
        if (!$filterInput) continue;
        $filterInput = explode('+', $filterInput);

        $itemsToFilter = $app->cacheGet('editor', 3, 'imageList', $pgSlug, 'filterTexts-' . $filterId, function() use ($app, $items, $inUse, $filterId) {
            switch ($filterId) {
                case 'slug':
                    foreach ($items as $imgSlug => $img)
                        $return[$imgSlug] = true;
                    break;

                case 'alt':
                    foreach ($items as $imgSlug => $img) {
                        $emptyFound = false;
                        $return[$imgSlug] = '';
                        foreach ($img['alt'] as $lang => $alt) {
                            if (empty($alt)) $emptyFound = true;
                            else $return[$imgSlug] .= ' ' . $alt;
                        }
                        if ($emptyFound) $return[$imgSlug] = '{{empty}}' . $return[$imgSlug];
                        $return[$imgSlug] = gPrepareTextForSearch($return[$imgSlug]);
                    }
                    break;

                case 'inuse':
                    foreach ($items as $imgSlug => $img) {
                        if (!isset($inUse[$imgSlug])) {
                            $return[$imgSlug] = '{{empty}}';
                            continue;
                        }
                        $return[$imgSlug] = '';
                        foreach ($inUse[$imgSlug] as $itemKey => $item) {
                            foreach ($item as $itemVal) {
                                $return[$imgSlug] .= ' ' . $itemKey . '/' . $itemVal;
                            }
                        }
                        $return[$imgSlug] = gPrepareTextForSearch($return[$imgSlug]);
                    }
                    break;

                case 'type':
                    foreach ($items as $imgSlug => $img) {
                        if (!isset($img['extra']['type'])) {
                            $return[$imgSlug] = '{{empty}}';
                            continue;
                        }
                        $return[$imgSlug] = gPrepareTextForSearch(t($img['extra']['type']));
                    }

                    break;
            }

            return $return;
        });


        // slugs search in keys
        if ($filterId == 'slug') {
            foreach ($itemsToFilter as $itemId => $text) {
                $filterFound = true;
                foreach ($filterInput as $word) {
                    $word = gPrepareTextForSearch($word);
                    $word = str_replace(' ', '-', $word);
                    if (strpos($itemId, $word) === false) {
                        $filterFound = false;
                    }
                }
                if (!$filterFound) $itemsFiltered[$itemId] = true;
            }
            continue;
        }

        // others search in values
        foreach ($itemsToFilter as $itemId => $text) {
            $filterFound = true;
            foreach ($filterInput as $word) {
                $word = gPrepareTextForSearch($word);
                if (strpos($text, $word) === false) {
                    $filterFound = false;
                }
            }
            if (!$filterFound) $itemsFiltered[$itemId] = true;
        }
    }
    if ($itemsFiltered) $rows = array_diff_key($rows, $itemsFiltered);
}




// pagination

$pagination = new Pagination((int) ($_POST['page'] ?? 1), (int) ($_POST['itemsPerPage'] ?? 50));
$rowsFiltered = count($rows);
$pagination->setItemsTotal($rowsFiltered);
$offset = $pagination->itemFirst - 1;
$length = $pagination->itemsPerPage;
if ($length >= $pagination->itemsTotal) $length = null;

$rows = array_slice($rows, $offset, $length);




// finish

$hdTitle = $geConf[$pgSlug]['gcTitlePlural'];
$pgTitle = $geConf[$pgSlug]['gcTitlePlural'];
