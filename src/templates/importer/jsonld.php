<?php

$editor->layout = 'none';

$url = $_GET['url'] ?? '';
$url = preg_replace('/\?.*/', '', $url);

if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
    $url = preg_replace('~^https?://([^\.]+)?\.?facebook\.(\w)+/~', 'https://pt-pt.facebook.com/', $url);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    curl_setopt($ch, CURLOPT_REFERER, "http://www.facebook.com");
    $page = curl_exec($ch) or die(curl_error($ch));
    curl_close($ch);

    if (preg_match('~<script .*?type="application/ld\+json">(.*?)</script>~ms', $page, $m)) {
        $r = json_decode($m[1], true);
    } else {
        $r['error'] = t('jsonld not found');
    }

} else {
    $r['error'] = t('Invalid url');
}
$r['url'] = $url;


$r = array_map_recursive(function($a) { return strip_tags($a, ALLOWED_TAGS); }, $r);


header('Content-Type: application/json');
exit(json_encode($r, JSON_PRETTY_PRINT));
