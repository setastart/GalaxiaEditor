<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia\Scrape;


use Galaxia\Text;


class Scrape {

    const string ERROR = 'error';
    const string INFO  = 'info';
    const string DATA  = 'data';

    const array RETURN = [
        self::ERROR => '',
        self::INFO  => [],
        self::DATA  => [],
    ];

    const string INFO_IMAGE_EXISTS         = 'Image already exists.';
    const string INFO_IMAGE_DOWNLOADED     = 'Image downloaded.';
    const string INFO_IMAGE_NOT_DOWNLOADED = 'Could not download image.';

    const string ERROR_INVALID_URL              = 'Invalid Url.';
    const string ERROR_COULD_NOT_DOWNLOAD_PAGE  = 'Could not download page.';
    const string ERROR_JSON_LD_NOT_DECODED      = 'JSON-LD not decoded.';
    const string ERROR_JSON_LD_NOT_FOUND        = 'JSON-LD not found.';
    const string ERROR_INTERNAL_SCRAPER         = 'Internal scraper error.';
    const string ERROR_DOWNLOADED_PAGE_IS_EMPTY = 'Downloaded page is empty.';
    const string ERROR_FILE_NOT_FOUND           = 'File not found.';

    const string UA = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3';


    static function downloadHtml(string $url, string $referer = '', ?string $ua = null): array {
        $r = self::RETURN;

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return array_merge($r, [self::ERROR => self::ERROR_INVALID_URL]);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $ua ?? self::UA);
        curl_setopt($ch, CURLOPT_REFERER, $referer);
        $result = curl_exec($ch);

        if ($result === false) {
            return array_merge($r, [self::ERROR => self::ERROR_COULD_NOT_DOWNLOAD_PAGE]);
        }
        if (empty($result)) {
            return array_merge($r, [self::ERROR => self::ERROR_DOWNLOADED_PAGE_IS_EMPTY]);
        }
        return array_merge($r, [self::DATA => $result]);
    }


    static function localHtml($path): array {
        $r = self::RETURN;

        $html = file_get_contents($path);
        if (!$html) {
            return array_merge($r, [self::ERROR => self::ERROR_FILE_NOT_FOUND]);
        }

        return array_merge($r, [self::DATA => $html]);
    }


    static function getJsonLd(string $html): array {
        $r = self::RETURN;

        if (preg_match('~<script [^>]*type="application/ld\+json"[^>]*>(.*?)</script>~ms', $html, $m)) {
            $json = json_decode($m[1], true);
            if ($json == null) {
                return array_merge($r, [self::ERROR => self::ERROR_JSON_LD_NOT_DECODED]);
            }
            $r[self::DATA] = $json;
        } else {
            return array_merge($r, [self::ERROR => self::ERROR_JSON_LD_NOT_FOUND]);
        }

        return $r;
    }


    static function printJsonAndExit(array $r): never {
        header('Content-Type: application/json');
        exit(json_encode(self::resultClean($r), JSON_PRETTY_PRINT));
    }


    static function exitJsonOnError(array $r): void {
        if (!isset($r[self::ERROR])) {
            self::printJsonAndExit(array_merge($r, [self::ERROR => self::ERROR_INTERNAL_SCRAPER]));
        }
        if ($r[self::ERROR]) {
            self::printJsonAndExit($r);
        }
    }


    static function resultClean(array $r): array {
        $r[self::ERROR] = Text::t($r[self::ERROR]);
        foreach ($r[self::INFO] as $key => $val) {
            $r[self::INFO][$key] = Text::t($val);
        }

        array_walk_recursive($r, function(&$v) {
            $v = strip_tags($v, Text::ALLOWED_TAGS);
        });
        return $r;
    }

}
