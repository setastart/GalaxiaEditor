<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\AppImage;
use Galaxia\Flash;
use Galaxia\G;
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
