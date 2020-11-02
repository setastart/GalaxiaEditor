<?php

use Galaxia\AppImage;


$count = AppImage::deleteResizes($app->dirImage, $imgSlug);

info('Deleted image resizes: ' . $count);
redirect('edit/' . $pgSlug . '/' . $imgSlug);
