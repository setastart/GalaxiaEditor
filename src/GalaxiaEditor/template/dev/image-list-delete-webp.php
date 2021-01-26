<?php


use Galaxia\AppImage;
use Galaxia\Director;
use Galaxia\Flash;
use Galaxia\Text;


$editor->view = 'dev/dev';


$images = AppImage::list($app->dirImage);

$count = 0;
foreach ($images as $imgSlug => $mtime) {
    $count += AppImage::deleteWebp($app->dirImage, $imgSlug);
    touch($app->dirImage . $imgSlug . '/', $mtime);
}

$app->cacheDelete('editor');
Flash::info(sprintf(Text::t('Deleted %d Webp Images'), $count));

Director::redirect('edit/dev');
