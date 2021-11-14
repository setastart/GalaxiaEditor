<?php
/* Copyright 2017-2021 Ino Detelić & Zaloa G. Ramos

 - Licensed under the EUPL, Version 1.2 only (the "Licence");
 - You may not use this work except in compliance with the Licence.

 - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

 - Unless required by applicable law or agreed to in writing, software distributed
   under the Licence is distributed on an "AS IS" basis,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 - See the Licence for the specific language governing permissions and limitations under the Licence.
*/

namespace Galaxia\Scrape;


class Youtube {

    const TYPE          = '@type';
    const ID            = 'id';
    const URL           = 'url';
    const HASH          = 'hash';
    const STATUS        = 'status';
    const TITLE         = 'title';
    const LENGTHSECONDS = 'lengthSeconds';
    const CHANNELID     = 'channelId';
    const CHANNEL       = 'channel';
    const ISPRIVATE     = 'isPrivate';
    const ISUNLISTED    = 'isUnlisted';
    const DATE          = 'date';
    const VIEWCOUNT     = 'viewCount';
    const IMG_SLUG      = 'imgSlug';
    const IMG_URL       = 'imgUrl';


    const RETURN_YOUTUBE = [
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

    const URL_YOUTUBE_PREFIX = 'https://www.youtube.com/watch?v=';
    const URL_SCRAPE_PREFIX  = 'https://www.youtube.com/get_video_info?video_id=';

    const URL_IMG_PREFIX = 'https://img.youtube.com/vi/';
    const URL_IMG_SUFFIX = '/hqdefault.jpg';

    const SLUG_IMG_PREFIX = 'youtube-';


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
