<?php

use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


E::$pgTitle = Text::t('Upload Images');
E::$hdTitle = Text::t('Upload Images');

$options = [];
if (E::$section['gcImageTypes']) {
    foreach (E::$section['gcImageTypes'] as $tag => $bounds) {
        $options[$tag] = ['label' => $tag];
    }
}

E::$imgInputs = [
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

foreach (E::$imgInputs as $key => $input) {
    E::$imgInputs[$key] = array_merge(Input::PROTO_INPUT, $input);
}
