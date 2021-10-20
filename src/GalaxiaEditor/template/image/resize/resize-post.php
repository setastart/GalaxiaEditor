<?php

use Galaxia\G;
use Galaxia\Flash;
use GalaxiaEditor\input\Input;


$editor->view = 'image/resize/resize';



// item validation

foreach ($inputs as $inputKey => $input) {
    if (!isset($_POST[$input['name']])) {
        $input['errors'][] = 'Required.';
        continue;
    }
    $value = $_POST[$input['name']];
    $input = Input::validate($input, $value);
    $inputs[$inputKey] = $input;
}

foreach ($inputs as $input) {
    foreach ($input['errors'] as $msg) {
        Flash::error($msg, 'form', $input['name']);
        if ($input['lang']) {
            $langSelectClass[$input['lang']] = 'btn-red';
        }
    }
}

if (Flash::hasError()) return;




// resize images

$files = [[
    'tmp_name' => $app->dirImage . $imgSlug . '/' . $imgSlug . $img['ext'],
    'name' => $imgSlug,
]];
$uploaded = $app->imageUpload($files, true, $_POST['resize']);




// finish

$app->cacheDelete(['app', 'fastroute']);
$app->cacheDelete('editor', 'imageList-' . $pgSlug . '*');
G::redirect('edit/' . $pgSlug . '/' . $imgSlug);
