<?php

use Galaxia\AppImage;
use Galaxia\Director;
use Galaxia\Flash;


$count = AppImage::deleteWebp($app->dirImage, $imgSlug);

Flash::info('Deleted webp images: ' . $count);
Director::redirect('edit/' . $pgSlug . '/' . $imgSlug);
