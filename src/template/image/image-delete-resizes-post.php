<?php

use Galaxia\AppImage;
use Galaxia\Director;


$count = AppImage::deleteResizes($app->dirImage, $imgSlug);

info('Deleted image resizes: ' . $count);
Director::redirect('edit/' . $pgSlug . '/' . $imgSlug);
