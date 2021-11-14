<?php

use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


$pgTitle = Text::t('Upload Images');
$hdTitle = Text::t('Upload Images');

$options = [];
if (E::$conf[$pgSlug]['gcImageTypes']) {
    foreach (E::$conf[$pgSlug]['gcImageTypes'] as $tag => $bounds) {
        $options[$tag] = ['label' => $tag];
    }
}

$inputs = [
    'images' => [
        'label'   => 'Images',
        'name'    => 'images[]',
        'type'    => 'image',
        'options' => [
            'multiple' => true,
            'type'     => $options,
            'existing' => [
                'ignore'  => ['label' => 'Ignore'],
                'rename'  => ['label' => 'Rename', 'cssClass' => 'btn-yellow'],
                'replace' => ['label' => 'Replace', 'cssClass' => 'btn-red'],
            ],
        ],
    ],
];

foreach ($inputs as $key => $input) {
    $inputs[$key] = array_merge(Input::PROTO_INPUT, $input);
}
