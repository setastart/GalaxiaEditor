<?php

use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Flash;


$count = AppImage::deleteResizes($app->dirImage, $imgSlug);

Flash::info('Deleted image resizes: ' . $count);
G::redirect('edit/' . $pgSlug . '/' . $imgSlug);
