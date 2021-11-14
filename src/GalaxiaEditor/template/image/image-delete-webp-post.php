<?php

use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Flash;
use GalaxiaEditor\E;


$count = AppImage::deleteWebp(G::dirImage(), E::$imgSlug);

Flash::info('Deleted webp images: ' . $count);
G::redirect('edit/' . E::$pgSlug . '/' . E::$imgSlug);
