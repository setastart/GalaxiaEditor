<?php

use Galaxia\AppImage;
use Galaxia\Text;


$editor->layout = 'none';

$fileNameRaw = $_GET['filename'];

$fileSlug = $fileName = pathinfo($fileNameRaw, PATHINFO_FILENAME);

$fileName = Text::normalize($fileName, ' ', '.+');
$fileSlug = Text::formatSlug($fileSlug);


if (AppImage::valid($app->dirImage, $fileSlug) === false) {
    exit(json_encode([
        'status' => 'ok',
        'slug' => $fileSlug,
        'alt' => $fileName,
    ], JSON_PRETTY_PRINT));
}


exit(json_encode([
    'status' => 'error',
    'slug' => $fileSlug,
    'alt' => $fileName,
], JSON_PRETTY_PRINT));