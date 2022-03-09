<?php

use Galaxia\G;
use Galaxia\File;
use Galaxia\Flash;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


G::$editor->view = 'image/replace/replace';




// item validation

foreach (File::uploadRemoveErrors('images') as $error)
    E::$imgInputs['images']['errors'][] = 'Error uploading ' . $error['file'] . ' - ' . $error['msg'];

if (count($_FILES['images']['name'] ?? []) != 1)
    E::$imgInputs['images']['errors'][] = 'Required 1 image.';

foreach (E::$imgInputs as $inputKey => $input) {
    if ($inputKey == 'images') continue;
    if (!isset($_POST[$input['name']])) {
        $input['errors'][] = 'Required.';
        continue;
    }
    $value             = $_POST[$input['name']];
    $input             = Input::validate($input, $value);
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




// replace images

$files = [[
    'tmp_name' => $_FILES['images']['tmp_name'][0],
    'name' => E::$imgSlug,
]];
$uploaded = G::imageUpload($files, true, $_POST['resize']);




// finish

G::cacheDelete(['app', 'fastroute']);
G::cacheDelete('editor', 'imageList-' . E::$pgSlug . '*');
G::redirect('edit/' . E::$pgSlug . '/' . E::$imgSlug);
