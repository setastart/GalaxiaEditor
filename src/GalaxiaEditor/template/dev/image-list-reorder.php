<?php


use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Flash;
use Galaxia\Text;


G::$editor->view = 'dev/dev';


$images = AppImage::list(G::dirImage());

foreach ($images as $imgSlug => $mtimeDir) {
    if (!$ext = AppImage::valid(G::dirImage(), $imgSlug)) continue;
    $mtime = filemtime(G::dirImage() . $imgSlug . '/' . $imgSlug . $ext);
    touch(G::dirImage() . $imgSlug . '/', $mtime);
}

G::cacheDelete('editor');
Flash::info(Text::t('Reordered images by upload time'));

G::redirect('edit/' . G::$editor->imageSlug);
