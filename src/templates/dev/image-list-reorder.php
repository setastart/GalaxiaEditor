<?php


$editor->view = 'dev/dev';


$images = gImageList($app->dirImages);

foreach ($images as $imgSlug => $mtimeDir) {
    if (!$ext = gImageValid($app->dirImages, $imgSlug)) continue;
    $mtime = filemtime($app->dirImages . $imgSlug . '/' . $imgSlug . $ext);
    touch($app->dirImages . $imgSlug . '/', $mtime);
}

$app->cacheDelete('editor');
info(t('Reordered images by upload time'));

redirect('edit/images');
