<?php


use Galaxia\AppImage;
use Galaxia\Director;
use Galaxia\Scrape\Scrape;
use Galaxia\Scrape\Vimeo;
use Galaxia\Text;


$editor->layout = 'none';

$id = Text::h($_GET['id']) ?? '';
$r = Vimeo::getVideoFromId($id);

if ($r[Scrape::DATA][Vimeo::IMG_SLUG] ?? '') {
    $app     = Director::getApp();
    $imgSlug = $r[Scrape::DATA][Vimeo::IMG_SLUG];

    if (AppImage::valid($app->dirImage, $imgSlug)) {
        $r[Scrape::INFO][$id] = Scrape::INFO_IMAGE_EXISTS;
    } else {
        $imgUrl = $r[Scrape::DATA][Vimeo::IMG_URL];
        $uploadedImages = $app->imageUpload([$imgUrl => $imgSlug], true, 1920, 'vimeo');
        if (empty($uploadedImages)) {
            $r[Scrape::INFO][$id] = Scrape::INFO_IMAGE_NOT_DOWNLOADED;
        } else {
            $r[Scrape::INFO][$id] = Scrape::INFO_IMAGE_DOWNLOADED;
        }
    }
}

Scrape::printJsonAndExit($r);
