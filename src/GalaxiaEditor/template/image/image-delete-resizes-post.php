<?php

use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Flash;


$count = AppImage::deleteResizes(G::dirImage(), $imgSlug);

Flash::info('Deleted image resizes: ' . $count);
G::redirect('edit/' . $pgSlug . '/' . $imgSlug);
