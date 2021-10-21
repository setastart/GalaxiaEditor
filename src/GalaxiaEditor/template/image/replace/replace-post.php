<?php

use Galaxia\G;
use Galaxia\File;
use Galaxia\Flash;
use GalaxiaEditor\input\Input;


$editor->view = 'image/replace/replace';




// item validation

foreach (File::uploadRemoveErrors('images') as $error)
    $inputs['images']['errors'][] = 'Error uploading ' . $error['file'] . ' - ' . $error['msg'];

if (count($_FILES['images']['name'] ?? []) != 1)
    $inputs['images']['errors'][] = 'Required 1 image.';

foreach ($inputs as $inputKey => $input) {
    if ($inputKey == 'images') continue;
    if (!isset($_POST[$input['name']])) {
        $input['errors'][] = 'Required.';
        continue;
    }
    $value             = $_POST[$input['name']];
    $input             = Input::validate($input, $value);
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




// replace images

$files = [[
    'tmp_name' => $_FILES['images']['tmp_name'][0],
    'name' => $imgSlug,
]];
$uploaded = G::imageUpload($files, true, $_POST['resize']);




// finish

$app->cacheDelete(['app', 'fastroute']);
$app->cacheDelete('editor', 'imageList-' . $pgSlug . '*');
G::redirect('edit/' . $pgSlug . '/' . $imgSlug);
