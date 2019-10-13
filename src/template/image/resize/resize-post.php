<?php

$editor->view = 'image/resize/resize';



// item validation

foreach ($inputs as $inputKey => $input) {
    if (!isset($_POST[$input['name']])) {
        $input['errors'][] = 'Required.';
        continue;
    }
    $value = $_POST[$input['name']];
    $input = validateInput($input, $value);
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




// resize images

$_FILES['images']['name'][0] = $imgSlug;
$uploaded = $app->imageUpload([$app->dirImage . $imgSlug . '/' . $imgSlug . $img['ext'] => $imgSlug], true, $_POST['resize']);




// finish

$app->cacheDelete(['app', 'fastroute']);
$app->cacheDelete('editor', 'imageList', $pgSlug);
redirect('edit/' . $pgSlug . '/' . $imgSlug);
