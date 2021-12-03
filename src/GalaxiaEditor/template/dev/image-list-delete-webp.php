<?php


use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Flash;
use Galaxia\Text;


G::$editor->view = 'dev/dev';


$images = AppImage::list(G::dirImage());

$count = 0;
foreach ($images as $imgSlug => $mtime) {
    $count += AppImage::deleteWebp(G::dirImage(), $imgSlug);
    touch(G::dirImage() . $imgSlug . '/', $mtime);
}

G::cacheDelete('editor');
Flash::info(sprintf(Text::t('Deleted %d Webp Images'), $count));

G::redirect('edit/dev');
