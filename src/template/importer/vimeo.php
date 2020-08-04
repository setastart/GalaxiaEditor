<?php


use Galaxia\Director;
use Galaxia\Scrape\Scrape;
use Galaxia\Scrape\Vimeo;


$editor->layout = 'none';

$id = h($_GET['id']) ?? '';
$r = Vimeo::getVideoFromId($id);

if ($r[Scrape::DATA][Vimeo::IMG_SLUG] ?? '') {
    $app     = Director::getApp();
    $imgSlug = $r[Scrape::DATA][Vimeo::IMG_SLUG];

    if (gImageValid($app->dirImage, $imgSlug)) {
        $r[Scrape::INFO][$id] = Scrape::INFO_IMAGE_EXISTS;
    } else {
        $imgUrl = $r[Scrape::DATA][Vimeo::IMG_URL];;
        $uploadedImages = $app->imageUpload([$imgUrl => $imgSlug], true, 0, 'vimeo');
        if (empty($uploadedImages)) {
            $r[Scrape::INFO][$id] = Scrape::INFO_IMAGE_NOT_DOWNLOADED;
        } else {
            $r[Scrape::INFO][$id] = Scrape::INFO_IMAGE_DOWNLOADED;
        }
    }
}

Scrape::printJsonAndExit($r);
