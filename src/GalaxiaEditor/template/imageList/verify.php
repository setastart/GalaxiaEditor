<?php
// Copyright 2017-2023 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Text;


G::$editor->layout = 'layout-none';

$fileNameRaw = $_GET['filename'];

$fileSlug = $fileName = pathinfo($fileNameRaw, PATHINFO_FILENAME);

$fileName = Text::normalize($fileName, ' ', '.+');
$fileSlug = Text::formatSlug($fileSlug);


if (AppImage::valid(G::dirImage(), $fileSlug) === false) {
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
