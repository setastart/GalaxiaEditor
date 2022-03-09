<?php

use Galaxia\G;
use Galaxia\Flash;
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
$uploaded = G::imageUpload($files, true, $_POST['resize']);




// finish

G::cacheDelete(['app', 'fastroute']);
G::cacheDelete('editor', 'imageList-' . E::$pgSlug . '*');
G::redirect('edit/' . E::$pgSlug . '/' . E::$imgSlug);
