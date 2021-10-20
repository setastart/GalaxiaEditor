<?php

use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Scrape\Scrape;
use Galaxia\Scrape\Youtube;
use Galaxia\Text;


$editor->layout = 'none';


// $r = Youtube::getPlaylistVideos('https://www.youtube.com/playlist?list=PLi5BhhFIMLyjhkd2Vj-VjHXtWUM2oQNIm');
// Scrape::printJsonAndExit($r);

$id = Text::h($_GET['id']) ?? '';
$r = Youtube::getVideoFromId($id);

if ($r[Scrape::DATA][Youtube::IMG_SLUG] ?? '') {
    $app     = G::getApp();
    $imgSlug = $r[Scrape::DATA][Youtube::IMG_SLUG];

    if (AppImage::valid($app->dirImage, $imgSlug)) {
        $r[Scrape::INFO][$id] = Scrape::INFO_IMAGE_EXISTS;
    } else {
        $files = [[
            'tmp_name' => $r[Scrape::DATA][Youtube::IMG_URL],
            'name' => $imgSlug,
        ]];
        $uploadedImages = $app->imageUpload($files, true, 1920, 'vimeo');
        if (empty($uploadedImages)) {
            $r[Scrape::INFO][$id] = Scrape::INFO_IMAGE_NOT_DOWNLOADED;
        } else {
            $r[Scrape::INFO][$id] = Scrape::INFO_IMAGE_DOWNLOADED;
        }
    }
}

Scrape::printJsonAndExit($r);
