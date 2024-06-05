<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\AppCache;
use Galaxia\AppImage;
use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Text;


G::$editor->view = 'dev/dev';


$images = AppImage::list(G::dirImage());

$count = 0;
foreach ($images as $imgSlug => $mtime) {
    $count += AppImage::deleteWebp(G::dirImage(), $imgSlug);
    touch(G::dirImage() . $imgSlug . '/', $mtime);
}

AppCache::delete(['editor']);
Flash::info(sprintf(Text::t('Deleted %d Webp Images'), $count));

G::redirect('edit/dev');
