<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\AppCache;
use Galaxia\AppImage;
use Galaxia\Flash;
use Galaxia\G;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


G::$editor->view = 'image/resize/resize';



// item validation

foreach (E::$imgInputs as $inputKey => $input) {
    if (!isset($_POST[$input['name']])) {
        $input['errors'][] = 'Required.';
        continue;
    }
    $value = $_POST[$input['name']];
    $input = Input::validate($input, $value);
    E::$imgInputs[$inputKey] = $input;
}

foreach (E::$imgInputs as $input) {
    foreach ($input['errors'] as $msg) {
        Flash::error($msg, 'form', $input['name']);
        if ($input['lang']) {
            E::$langSelectClass[$input['lang']] = 'btn-red';
        }
    }
}

if (Flash::hasError()) return;




// resize images

$files = [[
    'tmp_name' => G::dirImage() . E::$imgSlug . '/' . E::$imgSlug . E::$img['ext'],
    'name' => E::$imgSlug,
]];
$uploaded = AppImage::imageUpload($files, true, $_POST['resize'] ?? 0);




// finish

AppCache::delete(['app', 'fastroute']);
AppCache::delete(['editor'], 'imageList-' . E::$pgSlug . '*');
G::redirect('edit/' . E::$pgSlug . '/' . E::$imgSlug);
