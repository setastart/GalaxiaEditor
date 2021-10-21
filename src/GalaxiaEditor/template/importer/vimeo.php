<?php


use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Scrape\Scrape;
use Galaxia\Scrape\Vimeo;
use Galaxia\Text;


$editor->layout = 'none';

$id = Text::h($_GET['id']) ?? '';
$r = Vimeo::getVideoFromId($id);

if ($r[Scrape::DATA][Vimeo::IMG_SLUG] ?? '') {
    $app     = G::getApp();
    $imgSlug = $r[Scrape::DATA][Vimeo::IMG_SLUG];

    if (AppImage::valid(G::dirImage(), $imgSlug)) {
        $r[Scrape::INFO][$id] = Scrape::INFO_IMAGE_EXISTS;
    } else {
        $files = [[
            'tmp_name' => $r[Scrape::DATA][Vimeo::IMG_URL],
            'name' => $imgSlug,
        ]];

        $uploadedImages = G::imageUpload($files, true, 1920, 'vimeo');
        if (empty($uploadedImages)) {
            $r[Scrape::INFO][$id] = Scrape::INFO_IMAGE_NOT_DOWNLOADED;
        } else {
            $r[Scrape::INFO][$id] = Scrape::INFO_IMAGE_DOWNLOADED;
        }
    }
}

Scrape::printJsonAndExit($r);
