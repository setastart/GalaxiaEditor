<?php

use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Flash;
use Galaxia\Text;
use GalaxiaEditor\E;


G::$editor->view = 'image/delete/delete';



// item validation

if (!AppImage::delete(G::dirImage(), E::$imgSlug)) {
    Flash::error('image-delete-post - Unable to delete image: ' . Text::h(E::$imgSlug));

    return;
}




// finish

G::cacheDelete(['app', 'fastroute']);
G::cacheDelete('editor', 'imageList-' . E::$pgSlug . '*');
Flash::info('Deleted image: ' . Text::h(E::$imgSlug));
G::redirect('edit/' . E::$pgSlug);
