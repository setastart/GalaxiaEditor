<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\AppCache;
use Galaxia\AppImage;
use Galaxia\File;
use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Text;
use GalaxiaEditor\config\Config;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


G::$editor->view = 'image/new/new';


// item validation

foreach (File::uploadRemoveErrors('images') as $error)
    E::$imgInputs['images']['errors'][] = 'Error uploading ' . $error['file'] . ' - ' . $error['msg'];

if (empty($_FILES['images']['name'] ?? []))
    E::$imgInputs['images']['errors'][] = 'Required one or more images.';

foreach (E::$imgInputs as $inputKey => $input) {
    if ($inputKey == 'images') continue;
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

$uploaded = AppImage::imageUpload($files, false, 1920);
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
    E::$imgInputs['images']['errors'][] = $msg;
    Flash::error($msg, 'form', E::$imgInputs['images']);
}

if (Flash::hasError()) return;




// finish

AppCache::delete(['app', 'fastroute']);
AppCache::delete(['editor'], 'imageList-' . E::$pgSlug . '*');

if (isset($_POST['submitAndGoBack'])) G::redirect('edit/' . E::$pgSlug);
if (isset($_POST['submitAndAddMore'])) G::redirect('edit/' . E::$pgSlug . '/new');
if (count($uploaded) == 1) G::redirect('edit/' . E::$pgSlug . '/' . $uploaded[0]['slug']);
G::redirect('edit/' . E::$pgSlug);
