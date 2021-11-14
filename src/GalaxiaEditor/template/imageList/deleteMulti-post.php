<?php

use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Flash;
use Galaxia\Text;
use GalaxiaEditor\E;


$editor->view = 'imageList/deleteMulti';


foreach ($_POST['modules'][0]['imageDelete'] ?? [] as $imageNew) {
    foreach ($imageNew as $imgSlug) {
        if (AppImage::delete(G::dirImage(), $imgSlug)) {
            Flash::info('Deleted image: ' . Text::h($imgSlug));
        } else {
            Flash::error('Unable to delete image: ' . Text::h($imgSlug));
        }
    }
}


// finish

if (Flash::hasInfo()) {
    G::cacheDelete(['app', 'fastroute']);
    G::cacheDelete('editor', 'imageList-' . E::$pgSlug . '*');
}

G::redirect('edit/' . E::$pgSlug);
