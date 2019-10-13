<?php


$editor->view = 'dev/dev';


$images = gImageList($app->dirImage);

foreach ($images as $imgSlug => $mtimeDir) {
    if (!$ext = gImageValid($app->dirImage, $imgSlug)) continue;
    $mtime = filemtime($app->dirImage . $imgSlug . '/' . $imgSlug . $ext);
    touch($app->dirImage . $imgSlug . '/', $mtime);
}

$app->cacheDelete('editor');
info(t('Reordered images by upload time'));

redirect('edit/images');
