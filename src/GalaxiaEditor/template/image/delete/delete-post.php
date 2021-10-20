<?php

use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Flash;
use Galaxia\Text;


$editor->view = 'image/delete/delete';



// item validation

if (!AppImage::delete($app->dirImage, $imgSlug)) {
    Flash::error('image-delete-post - Unable to delete image: ' . Text::h($imgSlug));

    return;
}




// finish

$app->cacheDelete(['app', 'fastroute']);
$app->cacheDelete('editor', 'imageList-' . $pgSlug . '*');
Flash::info('Deleted image: ' . Text::h($imgSlug));
G::redirect('edit/' . $pgSlug);
