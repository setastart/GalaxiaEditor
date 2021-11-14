<?php

use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Flash;
use GalaxiaEditor\E;


$count = AppImage::deleteResizes(G::dirImage(), E::$imgSlug);

Flash::info('Deleted image resizes: ' . $count);
G::redirect('edit/' . E::$pgSlug . '/' . E::$imgSlug);
