<?php

use Galaxia\Scrape\Scrape;


$editor->layout = 'none';

$url = $_GET['url'] ?? '';
$url = preg_replace('~\?.*~', '', $url);

$url = preg_replace('~^https?://([^.]+)?\.?facebook\.(\w)+/~', 'https://pt-pt.facebook.com/', $url);

$html = Scrape::downloadHtml($url, 'http://www.facebook.com');
Scrape::exitJsonOnError($html);

$r = Scrape::getJsonLd($html[Scrape::DATA]);
Scrape::exitJsonOnError($r);


Scrape::printJsonAndExit($r);
