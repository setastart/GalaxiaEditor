<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

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
