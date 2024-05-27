<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\AppCache;
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

AppCache::deleteDir('editor');
Flash::info(Text::t('Reordered images by upload time'));

G::redirect('edit/' . G::$editor->imageSlug);
