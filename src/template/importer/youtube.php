<?php

use Galaxia\Director;
use Galaxia\Scrape\Scrape;
use Galaxia\Scrape\Youtube;

$editor->layout = 'none';


// $r = Youtube::getPlaylistVideos('https://www.youtube.com/playlist?list=PLi5BhhFIMLyjhkd2Vj-VjHXtWUM2oQNIm');
// Scrape::printJsonAndExit($r);

$id = h($_GET['id']) ?? '';
$r = Youtube::getVideoFromId($id);

if ($r[Scrape::DATA][Youtube::IMG_SLUG] ?? '') {
    $app     = Director::getApp();
    $imgSlug = $r[Scrape::DATA][Youtube::IMG_SLUG];

    if (gImageValid($app->dirImage, $imgSlug)) {
        $r[Scrape::INFO][$id] = Scrape::INFO_IMAGE_EXISTS;
    } else {
        $imgUrl = $r[Scrape::DATA][Youtube::IMG_URL];
        $uploadedImages = $app->imageUpload([$imgUrl => $imgSlug], true, 1920, 'vimeo');
        if (empty($uploadedImages)) {
            $r[Scrape::INFO][$id] = Scrape::INFO_IMAGE_NOT_DOWNLOADED;
        } else {
            $r[Scrape::INFO][$id] = Scrape::INFO_IMAGE_DOWNLOADED;
        }
    }
}

Scrape::printJsonAndExit($r);
