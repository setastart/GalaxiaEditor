<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia\Scrape;


class Youtube {

    const string TYPE          = '@type';
    const string ID            = 'id';
    const string URL           = 'url';
    const string HASH          = 'hash';
    const string STATUS        = 'status';
    const string TITLE         = 'title';
    const string LENGTHSECONDS = 'lengthSeconds';
    const string CHANNELID     = 'channelId';
    const string CHANNEL       = 'channel';
    const string ISPRIVATE     = 'isPrivate';
    const string ISUNLISTED    = 'isUnlisted';
    const string DATE          = 'date';
    const string VIEWCOUNT     = 'viewCount';
    const string IMG_SLUG      = 'imgSlug';
    const string IMG_URL       = 'imgUrl';


    const array RETURN_YOUTUBE = [
        self::TYPE          => 'Video',
        self::ID            => '',
        self::HASH          => '',
        self::STATUS        => '',
        self::TITLE         => '',
        self::LENGTHSECONDS => '',
        self::CHANNELID     => '',
        self::CHANNEL       => '',
        self::ISPRIVATE     => '',
        self::ISUNLISTED    => '',
        self::DATE          => '',
        self::VIEWCOUNT     => '',
        self::IMG_SLUG      => '',
        self::IMG_URL       => '',
    ];

    const string URL_YOUTUBE_PREFIX = 'https://www.youtube.com/watch?v=';
    const string URL_SCRAPE_PREFIX  = 'https://www.youtube.com/get_video_info?video_id=';

    const string URL_IMG_PREFIX = 'https://img.youtube.com/vi/';
    const string URL_IMG_SUFFIX = '/hqdefault.jpg';

    const string SLUG_IMG_PREFIX = 'youtube-';


    static function getVideoFromId(string $id): array {
        $r               = Scrape::RETURN;
        $r[Scrape::DATA] = self::RETURN_YOUTUBE;

        $html = Scrape::downloadHtml(self::URL_SCRAPE_PREFIX . $id, 'http://www.youtube.com');
        if ($html[Scrape::ERROR]) return Scrape::resultClean($html);
        parse_str($html[Scrape::DATA], $data);

        $videoData = json_decode($data['player_response'], true);

        $r[Scrape::DATA][self::ID]            = $id;
        $r[Scrape::DATA][self::URL]           = self::URL_YOUTUBE_PREFIX . $id;
        $r[Scrape::DATA][self::HASH]          = hash('fnv164', $id);
        $r[Scrape::DATA][self::STATUS]        = $videoData['playabilityStatus']['status'] ?? null;
        $r[Scrape::DATA][self::TITLE]         = $videoData['videoDetails']['title'] ?? null;
        $r[Scrape::DATA][self::LENGTHSECONDS] = $videoData['videoDetails']['lengthSeconds'] ?? null;
        $r[Scrape::DATA][self::CHANNELID]     = $videoData['videoDetails']['channelId'] ?? null;
        $r[Scrape::DATA][self::CHANNEL]       = $videoData['videoDetails']['author'] ?? null;
        $r[Scrape::DATA][self::ISPRIVATE]     = $videoData['videoDetails']['isPrivate'] ?? null;
        $r[Scrape::DATA][self::ISUNLISTED]    = $videoData['microformat']['playerMicroformatRenderer']['isUnlisted'] ?? null;
        $r[Scrape::DATA][self::DATE]          = $videoData['microformat']['playerMicroformatRenderer']['publishDate'] ?? null;
        $r[Scrape::DATA][self::VIEWCOUNT]     = $videoData['microformat']['playerMicroformatRenderer']['viewCount'] ?? null;

        $r[Scrape::DATA][self::IMG_SLUG] = self::SLUG_IMG_PREFIX . $r[Scrape::DATA][self::HASH];
        $r[Scrape::DATA][self::IMG_URL]  = self::URL_IMG_PREFIX . $id . self::URL_IMG_SUFFIX;

        return $r;
    }


    static function getPlaylistVideos(string $url): array {
        $r = Scrape::RETURN;

        $html = Scrape::downloadHtml($url, 'http://www.youtube.com');
        if ($html[Scrape::ERROR]) return Scrape::resultClean($html);

        preg_match_all('~watch\?v=([a-zA-Z0-9_-]{11})~m', $html[Scrape::DATA], $matches);
        foreach ($matches[1] ?? [] as $match) {
            $r[Scrape::DATA][$match] = true;
        }

        return $r;
    }

}
