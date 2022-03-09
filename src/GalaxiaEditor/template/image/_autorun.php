<?php

namespace GalaxiaEditor\template\image;

use Galaxia\AppImage;
use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\render\Load;


E::$pgTitle = Text::t(E::$section['gcTitleSingle']) . ': ' . E::$imgSlug;
E::$hdTitle = Text::t('Editing') . ' ' . E::$pgTitle;

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

E::$img = G::imageGet(E::$imgSlug, ['w' => 256, 'h' => 256, 'version' => time(), 'extra' => ['type']]);
E::$img['resizes'] = AppImage::resizes(G::dirImage(), E::$imgSlug) ?? [];


E::$imgInputs = [
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
    E::$imgInputs['alt_' . $lang] = [
        'label'       => 'Alt',
        'name'        => 'alt_' . $lang,
        'type'        => 'text',
        'options'     => ['minlength' => 0, 'maxlength' => 255],
        'lang'        => $lang,
        'prefix'      => 'alt_',
        'value'       => E::$img['alt'][$lang] ?? '',
        'valueFromDb' => E::$img['alt'][$lang] ?? '',
    ];
}


if (E::$section['gcImageTypes']) {
    $options = [];
    foreach (E::$section['gcImageTypes'] as $tag => $bounds) {
        $options[$tag] = ['label' => $tag];
    }
    E::$imgInputs['type'] = [
        'label'       => 'Type',
        'name'        => 'type',
        'type'        => 'select',
        'options'     => $options,
        'value'       => E::$img['extra']['type'] ?? '',
        'valueFromDb' => E::$img['extra']['type'] ?? '',
    ];
}


$mtime = date('Y-m-d H:i:s', E::$img['mtime']);
E::$imgInputs['timestampM'] = [
    'label'       => 'Date and Time',
    'name'        => 'timestampM',
    'type'        => 'timestamp',
    'value'       => $mtime,
    'valueFromDb' => $mtime,
];


E::$showSwitchesLang = count(G::langs()) > 1;
foreach (G::langs() as $lang) {
    E::$langSelectClass[$lang] = '';
}

