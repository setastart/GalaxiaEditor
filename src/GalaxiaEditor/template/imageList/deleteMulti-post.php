<?php
// Copyright 2017-2023 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\AppImage;
use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Text;
use GalaxiaEditor\E;


G::$editor->view = 'imageList/deleteMulti';


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
