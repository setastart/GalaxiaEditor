<?php


$editor->view = 'dev/dev';


$images = gImageList($app->dirImages);

$count = 0;
foreach ($images as $imgSlug => $mtime) {
    $count += gImageDeleteResizes($app->dirImages, $imgSlug);
    touch($app->dirImages . $imgSlug . '/', $mtime);
}

$app->cacheDelete('editor');
info(sprintf(t('Deleted %d image resizes'), $count));

redirect('edit/dev');
