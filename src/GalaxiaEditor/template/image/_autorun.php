<?php


use Galaxia\AppImage;
use Galaxia\Director;
use Galaxia\Flash;
use Galaxia\Text;
use GalaxiaEditor\render\Load;


$pgTitle = Text::t($geConf[$pgSlug]['gcTitleSingle']) . ': ' . $imgSlug;
$hdTitle = Text::t('Editing') . ' ' . $pgTitle;

$item   = $geConf[$pgSlug]['gcImage'];
$action ??= '';


// skip for new item page

if ($imgSlug == 'new') return;




// item validation

if (!AppImage::valid($app->dirImage, $imgSlug)) {
    Flash::error(sprintf(Text::t('Image \'%s\' does not exist.'), Text::h($imgSlug)));
    Director::redirect('edit/' . $pgSlug);
}


$inUse = Load::imagesInUse($geConf, $pgSlug)[$imgSlug] ?? [];
// geD($inUse);

// load image and build inputs

$img = $app->imageGet($imgSlug, ['w' => 256, 'h' => 256, 'version' => time(), 'extra' => ['type']]);
$img['resizes'] = AppImage::resizes($app->dirImage, $imgSlug) ?? [];


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


foreach ($app->locales as $lang => $locale) {
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


if ($geConf[$pgSlug]['gcImageTypes']) {
    $options = [];
    foreach ($geConf[$pgSlug]['gcImageTypes'] as $tag => $bounds) {
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


$showSwitchesLang = count($app->langs) > 1 ? true : false;
$langSelectClass = [];
foreach ($app->langs as $lang) {
    $langSelectClass[$lang] = '';
}

