<?php

$editor->view = 'image/delete/delete';




// item validation

if (!gImageDelete($app->dirImages, $imgSlug)) {
    error('image-delete-post - Unable to delete image: ' . h($imgSlug));
    return;
}




// finish

$app->cacheDelete(['app', 'fastroute']);
$app->cacheDelete('editor', 'imageList', $pgSlug);
info('Deleted image: ' . h($imgSlug));
redirect('edit/' . $pgSlug);
