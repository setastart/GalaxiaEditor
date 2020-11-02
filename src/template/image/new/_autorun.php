<?php

use GalaxiaEditor\input\Input;


$pgTitle = t('+ Add') . ' ' . t($geConf[$pgSlug]['gcTitleSingle']);
$hdTitle = t('+ Add') . ' ' . t($geConf[$pgSlug]['gcTitleSingle']);

$inputs = [
    'images' => [
        'label'   => 'Images',
        'name'    => 'images[]',
        'type'    => 'image',
        'options' => ['multiple' => true],
    ],
];


if ($geConf[$pgSlug]['gcImageTypes']) {
    $options = [];
    foreach ($geConf[$pgSlug]['gcImageTypes'] as $tag) {
        $options[$tag] = ['label' => $tag];
    }
    $inputs['type'] = [
        'label'       => 'Type',
        'name'        => 'type',
        'type'        => 'select',
        'options'     => $options,
    ];
}


$inputs['replace'] = [
    'label' => 'Replace existing images',
    'name'  => 'replace',
    'type'  => 'radio',
    'value' => '0',
    'options' => [
        '1' => ['label' => 'Yes', 'cssClass' => 'btn-red'],
        '0' => ['label' => 'No', 'cssClass' => 'btn-blue'],
    ],
];


$inputs['resize'] = [
    'label' => 'Resize images to fit',
    'name'  => 'resize',
    'type'  => 'select',
    'value' => '1920',
    'options' => [
        '0' => ['label' => 'No', 'cssClass' => 'btn-blue'],
        '640' => ['label' => '640', 'cssClass' => 'btn-red'],
        '960' => ['label' => '960', 'cssClass' => 'btn-red'],
        '1440' => ['label' => '1440', 'cssClass' => 'btn-red'],
        '1920' => ['label' => '1920', 'cssClass' => 'btn-red'],
        '2560' => ['label' => '2560', 'cssClass' => 'btn-red'],
    ],
];




foreach ($inputs as $key => $input) {
    $inputs[$key] = array_merge(Input::PROTO_INPUT, $input);
}
