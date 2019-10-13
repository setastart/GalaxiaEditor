<?php


$editor->view = 'dev/dev';


$images = gImageList($app->dirImage);

$count = 0;
foreach ($images as $imgSlug => $mtime) {
    $count += gImageDeleteResizes($app->dirImage, $imgSlug);
    touch($app->dirImage . $imgSlug . '/', $mtime);
}

$app->cacheDelete('editor');
info(sprintf(t('Deleted %d image resizes'), $count));

redirect('edit/dev');
