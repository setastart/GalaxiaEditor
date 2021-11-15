<?php

use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Pagination;
use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\render\Load;


// ajax

if (E::$req->xhr) {
    $editor->layout = 'none';
    $editor->view = 'imageList/results';
    if (($_POST['imageListType'] ?? '') == 'image-select') $editor->view = 'imageList/selectResults';
}




// get images using cache

$items = G::cache('editor', 2, 'imageList-' . E::$pgSlug . '-items', function() use ($app) {
    $items = [];
    $imageList = AppImage::list(G::dirImage());

    foreach ($imageList as $imgSlug => $mtime) {
        if (!$img = G::imageGet($imgSlug, ['w' => 256, 'h' => 256, 'extra' => ['type'], 'version' => 'mtime', 'fileSize' => true, 'loading' => false], false)) continue;
        $items[$imgSlug] = $img;
    }

    uasort($items, function($a, $b) {
        if ($a['mtime'] == $b['mtime']) return strnatcmp($a['name'], $b['name']);
        return $b['mtime'] <=> $a['mtime'];
    });


    return $items;
});



// get in use items using cache

$inUse = Load::imagesInUse();




// make html for all rows, using cache

switch ($_POST['imageListType'] ?? '') {
    case 'image-select':
        $rows = G::cache('editor', 3, 'imageList-' . E::$pgSlug . '-rows-select', function() use ($app, $items, $inUse) {
            $rows = [];
            $imgTypes = [];
            $currentColor = 0;

            foreach ($items as $imgSlug => $img) {
                if (isset($img['extra']['type']))
                    if (!isset($imgTypes[$img['extra']['type']])) $imgTypes[$img['extra']['type']] = $currentColor++;

                $cssInUse = '';
                if (isset($inUse[$imgSlug])) $cssInUse = ' inUse';
$ht = '';
$ht .= '<button type="button" id="' . Text::h($imgSlug) . '" class="imageSelectItem' . $cssInUse . '">' . PHP_EOL;
$ht .= '    <figure>' . PHP_EOL;
$ht .= '        ' . G::image($img) . PHP_EOL;
$ht .= '    </figure>' . PHP_EOL;
$ht .= '    <p>' . Text::h($imgSlug) . '</p>' . PHP_EOL;

$ht .= '    <div class="meta">'. PHP_EOL;
                if (!empty(E::$section['gcImageTypes'])) {
                    if (isset($img['extra']['type'])) {
$ht .= '        <div class="tag brewer-' . Text::h(1 + ($imgTypes[$img['extra']['type']] % 9)) . '">' . Text::h(Text::t($img['extra']['type'])) . '</div>' . PHP_EOL;
                    }
                }
                if (isset($inUse[$imgSlug])) {
$ht .= '        <div class="tag black inUse">' . Text::t('In Use') . '</div>' . PHP_EOL;
                }
$ht .= '    </div>' . PHP_EOL;

$ht .= '</button>' . PHP_EOL;
                $rows[$imgSlug] = $ht;
            }
            return $rows;
        });
        break;

    default:
        $rows = G::cache('editor', 3, 'imageList-' . E::$pgSlug . '-rows', function() use ($editor, $items, $inUse) {
            $rows = [];
            $imgTypes = [];
            $currentColor = 0;

            foreach ($items as $imgSlug => $img) {
                if (isset($img['extra']['type']))
                    if (!isset($imgTypes[$img['extra']['type']])) $imgTypes[$img['extra']['type']] = $currentColor++;
$ht = '';
$ht .= '<a class="row row-image" href="/edit/' . $editor->imageSlug .  '/' . $imgSlug . '">' . PHP_EOL;
$ht .= '    <div class="col flexT">' . PHP_EOL;
$ht .= '        <div class="col-thumb figure single">' . PHP_EOL;
$ht .= '            ' . G::image($img) . PHP_EOL;
$ht .= '        </div>' . PHP_EOL;
$ht .= '    </div>' . PHP_EOL;
$ht .= '    <div class="col flex1">' . PHP_EOL;
$ht .= '        <div><span class="input-label-lang">' . Text::t('Slug') . ':</span> ' . Text::h($imgSlug) . '</div>' . PHP_EOL;
$ht .= '        <div><span class="input-label-lang">' . Text::t('Dimensions') . ':</span> <small class="grey">' . Text::h($img['wOriginal'] . 'x' . $img['hOriginal']) . '</small></div>' . PHP_EOL;
$ht .= '        <div><span class="input-label-lang">' . Text::t('Size') . ':</span> <small class="grey">' . Text::h(number_format($img['fileSize'], 0, '', ',') . ' B') . '</small></div>' . PHP_EOL;
$ht .= '        <div><span class="input-label-lang">' . Text::t('Created') . ':</span> <small class="grey">' . Text::formatDate($img['mtime'], 'd MMM y - HH:mm') . '</small></div>' . PHP_EOL;
$ht .= '    </div>' . PHP_EOL;
$ht .= '    <div class="col flex1">' . PHP_EOL;
                if (empty($img['alt'])) {
$ht .= '        <div><span class="small red">' . Text::t('Empty') . '</span></div>' . PHP_EOL;
                } else {
                    foreach ($img['alt'] as $lang => $alt) {
                        if (empty($alt)) {
$ht .= '        <div><span class="small red">' . Text::t('Empty') . '</span></div>' . PHP_EOL;
                        } else {
$ht .= '        <div><span class="input-label-lang">' . $lang . ':</span> ' . Text::h($alt) . '</div>' . PHP_EOL;
                        }
                    }
                }
$ht .= '    </div>' . PHP_EOL;
$ht .= '    <div class="col flex1">' . PHP_EOL;
                if (isset($inUse[$imgSlug])) {
                    foreach ($inUse[$imgSlug] as $itemKey => $item) {
                        if (is_array($item)) {
                            foreach ($item as $itemVal) {
$ht .= '        <div>' . Text::h($itemKey) . ' / ' . $itemVal . '</div>' . PHP_EOL;
                            }
                        } else {
$ht .= '        <div>' . Text::h($itemKey) . ' / ' . $item . '</div>' . PHP_EOL;
                        }
                    }
                } else {
$ht .= '        <div><span class="small red">' . Text::t('Empty') . '</span></div>' . PHP_EOL;
                }
$ht .= '    </div>' . PHP_EOL;
                if (!empty(E::$section['gcImageTypes'])) {
$ht .= '    <div class="col flexD tags">' . PHP_EOL;
                    if (isset($img['extra']['type'])) {
$ht .= '        <div class="tag brewer-' . Text::h(1 + ($imgTypes[$img['extra']['type']] % 9)) . '">' . Text::h(Text::t($img['extra']['type'])) . '</div>' . PHP_EOL;
                    } else {
$ht .= '        <span class="small red">' . Text::t('Empty') . '</span>' . PHP_EOL;
                    }
$ht .= '    </div>' . PHP_EOL;
                }
$ht .= '</a>';
                $rows[$imgSlug] = $ht;
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

        $itemsToFilter = G::cache('editor', 3, 'imageList-' . E::$pgSlug . '-filterTexts-' . $filterId, function() use ($app, $items, $inUse, $filterId) {
            switch ($filterId) {
                case 'slug':
                    foreach ($items as $imgSlug => $img)
                        $return[$imgSlug] = true;
                    break;

                case 'alt':
                    foreach ($items as $imgSlug => $img) {
                        $return[$imgSlug] = '';
                        $emptyFound = empty($img['alt']);
                        foreach ($img['alt'] as $lang => $alt) {
                            if (empty($alt)) $emptyFound = true;
                            else $return[$imgSlug] .= ' ' . $alt;
                        }
                        if ($emptyFound) $return[$imgSlug] = '{{empty}}' . $return[$imgSlug];
                        $return[$imgSlug] = Text::formatSearch($return[$imgSlug]);
                    }
                    break;

                case 'inuse':
                    foreach ($items as $imgSlug => $img) {
                        $emptyFound = false;
                        if (!isset($inUse[$imgSlug])) {
                            $return[$imgSlug] = '{{empty}}';
                            continue;
                        }
                        $return[$imgSlug] = '';
                        foreach ($inUse[$imgSlug] as $itemKey => $item) {
                            if (empty($item)) $emptyFound = true;
                            if (is_array($item)) {
                                foreach ($item as $itemVal) {
                                    if (empty($itemVal)) $emptyFound = true;
                                    else $return[$imgSlug] .= ' ' . $itemKey . '/' . $itemVal;
                                }
                            }
                        }
                        if ($emptyFound) $return[$imgSlug] = '{{empty}}' . $return[$imgSlug];
                        $return[$imgSlug] = Text::formatSearch($return[$imgSlug]);
                    }
                    break;

                case 'type':
                    foreach ($items as $imgSlug => $img) {
                        $emptyFound = false;
                        if (!isset($img['extra']['type'])) {
                            $return[$imgSlug] = '{{empty}}';
                            continue;
                        }
                        if ($emptyFound) $return[$imgSlug] = '{{empty}}' . $return[$imgSlug];
                        $return[$imgSlug] = Text::formatSearch(Text::t($img['extra']['type']));
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
                    $word = Text::formatSearch($word);
                    $word = str_replace(' ', '-', $word);
                    if (!str_contains($itemId, $word)) {
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
                $word = Text::formatSearch($word);
                if (!str_contains($text, $word)) {
                    $filterFound = false;
                }
            }
            if (!$filterFound) $itemsFiltered[$itemId] = true;
        }
    }
    if ($itemsFiltered) $rows = array_diff_key($rows, $itemsFiltered);
}




// pagination

$pagination = new Pagination((int) ($_POST['page'] ?? 1), (int) ($_POST['itemsPerPage'] ?? 100));
$rowsFiltered = count($rows);
$pagination->setItemsTotal($rowsFiltered);
$offset = $pagination->itemFirst - 1;
$length = $pagination->itemsPerPage;
if ($length >= $pagination->itemsTotal) $length = null;

$rows = array_slice($rows, $offset, $length, true);




// finish

E::$hdTitle = Text::t(E::$section['gcTitlePlural']);
E::$pgTitle = Text::t(E::$section['gcTitlePlural']);
