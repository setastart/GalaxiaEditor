<?php

use Galaxia\Director;

$app = Director::getApp();

$editor->layout = 'none';

$r = [
    'error' => '',
];

$youtubeId = h($_GET['id']) ?? '';
$videoInfo = scrapeYoutubeVideoInfo($_GET['id']);

if ($videoInfo['error']) {
    $r['error'] = $videoInfo['error'];
    return $r;
}

$r = $videoInfo;

$r['imgSlug'] = 'youtube-' . $videoInfo['hash'];
if (!gImageValid($app->dirImage, $r['imgSlug'])) {
    $imgUrl         = 'https://img.youtube.com/vi/' . $youtubeId . '/hqdefault.jpg';
    $uploadedImages = $app->imageUpload([$imgUrl => $r['imgSlug']], true, 0, 'youtube');
    if (empty($uploadedImages)) {
        $r['info'][$youtubeId] = 'Image not uploaded';
        $r['imgSlug'] = '';
    }
}

// $r = array_map_recursive(function($a) { return strip_tags($a, ALLOWED_TAGS); }, $r);

header('Content-Type: application/json');
exit(json_encode($r, JSON_PRETTY_PRINT));
