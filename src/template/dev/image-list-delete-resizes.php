<?php


use Galaxia\AppImage;


$editor->view = 'dev/dev';


$images = AppImage::list($app->dirImage);

$count = 0;
foreach ($images as $imgSlug => $mtime) {
    $count += AppImage::deleteResizes($app->dirImage, $imgSlug);
    touch($app->dirImage . $imgSlug . '/', $mtime);
}

$app->cacheDelete('editor');
info(sprintf(t('Deleted %d image resizes'), $count));

redirect('edit/dev');
