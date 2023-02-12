<?php
// Copyright 2017-2023 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Flash;
use Galaxia\Text;


G::$editor->view = 'dev/dev';


$images = AppImage::list(G::dirImage());

$count = 0;
foreach ($images as $imgSlug => $mtime) {
    $count += AppImage::deleteResizes(G::dirImage(), $imgSlug);
    touch(G::dirImage() . $imgSlug . '/', $mtime);
}

G::cacheDelete('editor');
Flash::info(sprintf(Text::t('Deleted %d image resizes'), $count));

G::redirect('edit/dev');
