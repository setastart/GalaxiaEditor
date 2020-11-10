<?php

use Galaxia\AppImage;
use Galaxia\Director;
use Galaxia\Flash;


$count = AppImage::deleteResizes($app->dirImage, $imgSlug);

Flash::info('Deleted image resizes: ' . $count);
Director::redirect('edit/' . $pgSlug . '/' . $imgSlug);
