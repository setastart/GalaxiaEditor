<?php


use Galaxia\AppImage;
use Galaxia\Director;
use Galaxia\Flash;


$editor->view = 'dev/dev';


$images = AppImage::list($app->dirImage);

$count = 0;
foreach ($images as $imgSlug => $mtime) {
    $count += AppImage::deleteResizes($app->dirImage, $imgSlug);
    touch($app->dirImage . $imgSlug . '/', $mtime);
}

$app->cacheDelete('editor');
Flash::info(sprintf(t('Deleted %d image resizes'), $count));

Director::redirect('edit/dev');
