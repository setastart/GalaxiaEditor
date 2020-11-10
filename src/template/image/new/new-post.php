<?php

use Galaxia\Director;
use GalaxiaEditor\input\Input;


$editor->view = 'image/new/new';
$type         = '';




// item validation

foreach (gFileUploadRemoveErrors('images') as $error)
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
    $input = Input::validateInput($input, $value);
    if ($inputKey == 'type' && !$input['errors']) $type = $value;
    $inputs[$inputKey] = $input;
}

foreach ($inputs as $input) {
    foreach ($input['errors'] as $msg) {
        error($msg, 'form', $input['name']);
        if ($input['lang']) {
            $langSelectClass[$input['lang']] = 'btn-red';
        }
    }
}
if (hasError()) return;




// upload images

$uploaded = $app->imageUpload(array_combine($_FILES['images']['tmp_name'], $_FILES['images']['name']), $_POST['replace'], $_POST['resize']);
foreach ($uploaded as $img) {
    if ($type) {
        $file = $app->dirImage . $img['slug'] . '/' . $img['slug'] . '_type.txt';
        if (file_put_contents($file, $type) !== false) {
            info('Updated: ' . t('Image Type'));
        } else {
            error('Not updated: ' . t('Image Type'));
        }
    }

    if (!$img['replaced'] && $img['fileName']) {
        $file = $app->dirImage . $img['slug'] . '/' . $img['slug'] . '_alt_' . $app->lang . '.txt';
        if (file_put_contents($file, $img['fileName']) !== false) {
            info('Updated: ' . t('Image Alt'));
        } else {
            error('Not updated: ' . t('Image Alt'));
        }
    }
}
foreach (errors('errorBox') as $msg) {
    $inputs['images']['errors'][] = $msg;
    error($msg, 'form', $inputs['images']);
}

if (hasError()) return;




// finish

$app->cacheDelete(['app', 'fastroute']);
$app->cacheDelete('editor', 'imageList-' . $pgSlug);
if (isset($_POST['submitAndGoBack'])) Director::redirect('edit/' . $pgSlug);
if (isset($_POST['submitAndAddMore'])) Director::redirect('edit/' . $pgSlug . '/new');
if (count($uploaded) == 1) Director::redirect('edit/' . $pgSlug . '/' . $uploaded[0]['slug']);
Director::redirect('edit/' . $pgSlug);
