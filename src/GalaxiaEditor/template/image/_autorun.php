<?php


use Galaxia\AppImage;
use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\render\Load;


$pgTitle = Text::t(E::$conf[$pgSlug]['gcTitleSingle']) . ': ' . $imgSlug;
$hdTitle = Text::t('Editing') . ' ' . $pgTitle;

$item   = E::$conf[$pgSlug]['gcImage'];
$action ??= '';


// skip for new item page

if ($imgSlug == 'new') return;




// item validation

if (!AppImage::valid(G::dirImage(), $imgSlug)) {
    Flash::error(sprintf(Text::t('Image \'%s\' does not exist.'), Text::h($imgSlug)));
    G::redirect('edit/' . $pgSlug);
}


$inUse = Load::imagesInUse($pgSlug)[$imgSlug] ?? [];
// geD($inUse);

// load image and build inputs

$img = G::imageGet($imgSlug, ['w' => 256, 'h' => 256, 'version' => time(), 'extra' => ['type']]);
$img['resizes'] = AppImage::resizes(G::dirImage(), $imgSlug) ?? [];


$inputs = [
    'imgSlug'     => [
        'label'       => 'Slug',
        'name'        => 'imgSlug',
        'type'        => 'slug',
        'options'     => ['minlength' => 1, 'maxlength' => 128],
        'value'       => $imgSlug,
        'valueFromDb' => $imgSlug,
    ],
];


foreach (G::locales() as $lang => $locale) {
    $inputs['alt_' . $lang] = [
        'label'       => 'Alt',
        'name'        => 'alt_' . $lang,
        'type'        => 'text',
        'options'     => ['minlength' => 0, 'maxlength' => 255],
        'lang'        => $lang,
        'prefix'      => 'alt_',
        'value'       => $img['alt'][$lang] ?? '',
        'valueFromDb' => $img['alt'][$lang] ?? '',
    ];
}


if (E::$conf[$pgSlug]['gcImageTypes']) {
    $options = [];
    foreach (E::$conf[$pgSlug]['gcImageTypes'] as $tag => $bounds) {
        $options[$tag] = ['label' => $tag];
    }
    $inputs['type'] = [
        'label'       => 'Type',
        'name'        => 'type',
        'type'        => 'select',
        'options'     => $options,
        'value'       => $img['extra']['type'] ?? '',
        'valueFromDb' => $img['extra']['type'] ?? '',
    ];
}


$mtime = date('Y-m-d H:i:s', $img['mtime']);
$inputs['timestampM'] = [
    'label'       => 'Date and Time',
    'name'        => 'timestampM',
    'type'        => 'timestamp',
    'value'       => $mtime,
    'valueFromDb' => $mtime,
];


$showSwitchesLang = count(G::langs()) > 1;
$langSelectClass = [];
foreach (G::langs() as $lang) {
    $langSelectClass[$lang] = '';
}

