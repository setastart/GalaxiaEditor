<?php

use Galaxia\G;
use Galaxia\File;
use Galaxia\Flash;
use Galaxia\Text;
use GalaxiaEditor\config\Config;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


G::$editor->view = 'image/new/new';


// item validation

foreach (File::uploadRemoveErrors('images') as $error)
    $inputs['images']['errors'][] = 'Error uploading ' . $error['file'] . ' - ' . $error['msg'];

if (empty($_FILES['images']['name'] ?? []))
    $inputs['images']['errors'][] = 'Required one or more images.';

foreach ($inputs as $inputKey => $input) {
    if ($inputKey == 'images') continue;
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

$files = File::simplify($_FILES['images']);

$types = Config::getImageTypes();

foreach ($files as $i => $file) {
    if (isset($_POST['imgType'][$i]) && isset($types[$_POST['imgType'][$i]])) {
        $imgType = $_POST['imgType'][$i];
        $files[$i]['imgType'] = $imgType;
        $files[$i]['imgExisting'] = $_POST['imgExisting'][$i] ?? null;
        $files[$i]['toFit'] = $types[$imgType];
    }
}



// upload images

$uploaded = G::imageUpload($files, false, 1920);
foreach ($uploaded as $img) {
    if ($img['imgType'] ?? '') {
        $file = G::dirImage() . $img['slug'] . '/' . $img['slug'] . '_type.txt';
        if (file_put_contents($file, $img['imgType']) !== false) {
            Flash::info('Updated: ' . Text::t('Image Type'));
        } else {
            Flash::error('Not updated: ' . Text::t('Image Type'));
        }
    }

    if (!$img['replaced'] && $img['fileName']) {
        $file = G::dirImage() . $img['slug'] . '/' . $img['slug'] . '_alt_' . G::lang() . '.txt';
        if (file_put_contents($file, $img['fileName']) !== false) {
            Flash::info('Updated: ' . Text::t('Image Alt'));
        } else {
            Flash::error('Not updated: ' . Text::t('Image Alt'));
        }
    }
}
foreach (Flash::errors('errorBox') as $msg) {
    $inputs['images']['errors'][] = $msg;
    Flash::error($msg, 'form', $inputs['images']);
}

if (Flash::hasError()) return;




// finish

G::cacheDelete(['app', 'fastroute']);
G::cacheDelete('editor', 'imageList-' . E::$pgSlug . '*');

if (isset($_POST['submitAndGoBack'])) G::redirect('edit/' . E::$pgSlug);
if (isset($_POST['submitAndAddMore'])) G::redirect('edit/' . E::$pgSlug . '/new');
if (count($uploaded) == 1) G::redirect('edit/' . E::$pgSlug . '/' . $uploaded[0]['slug']);
G::redirect('edit/' . E::$pgSlug);
