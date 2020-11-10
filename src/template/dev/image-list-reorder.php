<?php


use Galaxia\AppImage;
use Galaxia\Director;


$editor->view = 'dev/dev';


$images = AppImage::list($app->dirImage);

foreach ($images as $imgSlug => $mtimeDir) {
    if (!$ext = AppImage::valid($app->dirImage, $imgSlug)) continue;
    $mtime = filemtime($app->dirImage . $imgSlug . '/' . $imgSlug . $ext);
    touch($app->dirImage . $imgSlug . '/', $mtime);
}

$app->cacheDelete('editor');
info(t('Reordered images by upload time'));

Director::redirect('edit/' . $editor->imageSlug);
