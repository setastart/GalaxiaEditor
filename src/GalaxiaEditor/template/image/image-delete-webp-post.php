<?php

use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Flash;


$count = AppImage::deleteWebp(G::dirImage(), $imgSlug);

Flash::info('Deleted webp images: ' . $count);
G::redirect('edit/' . $pgSlug . '/' . $imgSlug);
