<?php

use Galaxia\AppImage;
use Galaxia\Director;
use Galaxia\Scrape\Scrape;


$editor->layout = 'none';

$url = $_GET['url'] ?? '';
$url = preg_replace('~\?.*~', '', $url);

$url = preg_replace('~^https?://([^.]+)?\.?facebook\.(\w)+/~', 'https://pt-pt.facebook.com/', $url);

$html = Scrape::downloadHtml($url, 'http://www.facebook.com');
Scrape::exitJsonOnError($html);

$r = Scrape::getJsonLd($html[Scrape::DATA]);
Scrape::exitJsonOnError($r);


if (preg_match('~ src="(https://\S*?s720x720\S*?)"~m', $html[Scrape::DATA], $matches)) {
    $app     = Director::getApp();
    $imgSlug = 'jsonld-' . hash('fnv164', serialize($r));

    if (AppImage::valid($app->dirImage, $imgSlug)) {
        $r[Scrape::INFO][$imgSlug] = Scrape::INFO_IMAGE_EXISTS;
    } else {
        $files = [[
            'tmp_name' => html_entity_decode($matches[1], ENT_HTML5, 'UTF-8'),
            'name' => $imgSlug,
        ]];
        $uploadedImage = $app->imageUpload($files, true, 1920, 'jsonld')[0] ?? [];

        if (empty($uploadedImage)) {
            $r[Scrape::INFO][$imgSlug] = Scrape::INFO_IMAGE_NOT_DOWNLOADED;
        } else {
            $r[Scrape::INFO][$imgSlug]  = Scrape::INFO_IMAGE_DOWNLOADED;
            $r[Scrape::DATA]['imgSlug'] = $imgSlug;
            $r[Scrape::DATA]['imgSrc']  = $app->urlImages . $uploadedImage['slug'] . $uploadedImage['ext'];
        }

    }

};


Scrape::printJsonAndExit($r);
