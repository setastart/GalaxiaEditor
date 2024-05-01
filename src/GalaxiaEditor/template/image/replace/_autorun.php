<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


E::$pgTitle = Text::t('Replace') . ' ' . Text::t(E::$section['gcTitleSingle']);
E::$hdTitle = Text::t('Replace') . ' ' . Text::t(E::$section['gcTitleSingle']);


E::$imgInputs = [
    'images' => [
        'label' => 'Image',
        'name'  => 'images[]',
        'type'  => 'image',
    ],
];


E::$imgInputs['resize'] = [
    'label'   => 'Resize images to fit',
    'name'    => 'resize',
    'type'    => 'radio',
    'value'   => '1920',
    'options' => [
        '0'    => ['label' => 'No', 'cssClass' => 'btn'],
        '640'  => ['label' => '640', 'cssClass' => 'btn'],
        '960'  => ['label' => '960', 'cssClass' => 'btn'],
        '1440' => ['label' => '1440', 'cssClass' => 'btn'],
        '1920' => ['label' => '1920 HD', 'cssClass' => 'btn'],
        '2560' => ['label' => '2560', 'cssClass' => 'btn'],
        '3840' => ['label' => '3840 4K', 'cssClass' => 'btn'],
    ],
];


foreach (E::$imgInputs as $key => $input) {
    E::$imgInputs[$key] = array_merge(Input::PROTO_INPUT, $input);
}
