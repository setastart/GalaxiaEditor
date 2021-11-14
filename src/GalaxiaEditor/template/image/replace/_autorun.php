<?php

use Galaxia\G;
use Galaxia\Text;
use GalaxiaEditor\input\Input;


$pgTitle = Text::t('Replace') . ' ' . Text::t(G::$conf[$pgSlug]['gcTitleSingle']);
$hdTitle = Text::t('Replace') . ' ' . Text::t(G::$conf[$pgSlug]['gcTitleSingle']);


$inputs = [
    'images' => [
        'label'   => 'Image',
        'name'    => 'images[]',
        'type'    => 'image',
    ],
];


$inputs['resize'] = [
    'label' => 'Resize images to fit',
    'name'  => 'resize',
    'type'  => 'radio',
    'value' => '1920',
    'options' => [
        '0' => ['label' => 'No', 'cssClass' => 'btn'],
        '640' => ['label' => '640', 'cssClass' => 'btn'],
        '960' => ['label' => '960', 'cssClass' => 'btn'],
        '1440' => ['label' => '1440', 'cssClass' => 'btn'],
        '1920' => ['label' => '1920', 'cssClass' => 'btn'],
        '2560' => ['label' => '2560', 'cssClass' => 'btn'],
    ],
];


foreach ($inputs as $key => $input) {
    $inputs[$key] = array_merge(Input::PROTO_INPUT, $input);
}
