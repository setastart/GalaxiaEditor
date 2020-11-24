<?php

use Galaxia\AppImage;
use Galaxia\Director;
use Galaxia\Flash;
use Galaxia\Text;


$editor->view = 'imageList/deleteMulti';


foreach ($_POST['modules'][0]['imageDelete'] ?? [] as $imageNew) {
    foreach ($imageNew as $imgSlug) {
        if (AppImage::delete($app->dirImage, $imgSlug)) {
            Flash::info('Deleted image: ' . Text::h($imgSlug));
        } else {
            Flash::error('Unable to delete image: ' . Text::h($imgSlug));
        }
    }
}


// finish

if (Flash::hasInfo()) {
    $app->cacheDelete(['app', 'fastroute']);
    $app->cacheDelete('editor', 'imageList-' . $pgSlug . '*');
}

Director::redirect('edit/' . $pgSlug);
