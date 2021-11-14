<?php


use Galaxia\AppImage;
use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\render\Load;


$pgTitle = Text::t(E::$section['gcTitleSingle']) . ': ' . E::$imgSlug;
$hdTitle = Text::t('Editing') . ' ' . $pgTitle;

$item   = E::$section['gcImage'];


// skip for new item page

if (E::$imgSlug == 'new') return;




// item validation

if (!AppImage::valid(G::dirImage(), E::$imgSlug)) {
    Flash::error(sprintf(Text::t('Image \'%s\' does not exist.'), Text::h(E::$imgSlug)));
    G::redirect('edit/' . E::$pgSlug);
}


$inUse = Load::imagesInUse()[E::$imgSlug] ?? [];
// geD($inUse);

// load image and build inputs

$img = G::imageGet(E::$imgSlug, ['w' => 256, 'h' => 256, 'version' => time(), 'extra' => ['type']]);
$img['resizes'] = AppImage::resizes(G::dirImage(), E::$imgSlug) ?? [];


$inputs = [
    'imgSlug'     => [
        'label'       => 'Slug',
        'name'        => 'imgSlug',
        'type'        => 'slug',
        'options'     => ['minlength' => 1, 'maxlength' => 128],
        'value'       => E::$imgSlug,
        'valueFromDb' => E::$imgSlug,
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


if (E::$section['gcImageTypes']) {
    $options = [];
    foreach (E::$section['gcImageTypes'] as $tag => $bounds) {
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

