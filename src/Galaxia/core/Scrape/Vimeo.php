<?php
/* Copyright 2017-2020 Ino Detelić & Zaloa G. Ramos

 - Licensed under the EUPL, Version 1.2 only (the "Licence");
 - You may not use this work except in compliance with the Licence.

 - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

 - Unless required by applicable law or agreed to in writing, software distributed
   under the Licence is distributed on an "AS IS" basis,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 - See the Licence for the specific language governing permissions and limitations under the Licence.
*/

namespace Galaxia\Scrape;


class Vimeo {

    const TYPE     = '@type';
    const ID       = 'id';
    const HASH     = 'hash';
    const URL      = 'url';
    const TITLE    = 'title';
    const DATE     = 'date';
    const VIEWS    = 'views';
    const IMG_SLUG = 'imgSlug';
    const IMG_URL  = 'imgUrl';

    const RETURN_VIMEO = [
        self::TYPE     => 'Video',
        self::ID       => '',
        self::HASH     => '',
        self::URL      => '',
        self::TITLE    => '',
        self::DATE     => '',
        self::IMG_SLUG => '',
        self::IMG_URL  => '',
    ];

    const URL_VIMEO_PREFIX = 'https://vimeo.com/';

    const URL_IMG_PREFIX = 'https://i.vimeocdn.com/video/';
    const URL_IMG_SUFFIX = '.jpg';

    const SLUG_IMG_PREFIX = 'vimeo-';


    static function getVideoFromId(int $id): array {
        $r               = Scrape::RETURN;
        $r[Scrape::DATA] = self::RETURN_VIMEO;

        $html = Scrape::downloadHtml(self::URL_VIMEO_PREFIX . $id);
        if ($html[Scrape::ERROR]) return Scrape::resultClean($html);

        $json = Scrape::getJsonLd($html[Scrape::DATA]);
        if ($json[Scrape::ERROR]) return Scrape::resultClean($json);

        $json[Scrape::DATA] = array_filter($json[Scrape::DATA], function ($a) {
            return ($a['@type'] ?? '') == 'VideoObject';
        });
        $json[Scrape::DATA] = $json[Scrape::DATA][0];

        $r[Scrape::DATA][self::ID]    = (string)$id;
        $r[Scrape::DATA][self::HASH]  = hash('fnv164', $id);
        $r[Scrape::DATA][self::URL]   = self::URL_VIMEO_PREFIX . $id;
        $r[Scrape::DATA][self::TITLE] = html_entity_decode($json[Scrape::DATA]['name'] ?? '', ENT_HTML5);
        $r[Scrape::DATA][self::DATE]  = substr($json[Scrape::DATA]['uploadDate'] ?? '', 0, 10);

        preg_match('~video%2F(\d+)~', $json[Scrape::DATA]['thumbnailUrl'] ?? '', $m);
        if ($m[1] ?? []) {
            $r[Scrape::DATA][self::IMG_SLUG] = self::SLUG_IMG_PREFIX . $r[Scrape::DATA][self::HASH];
            $r[Scrape::DATA][self::IMG_URL]  = self::URL_IMG_PREFIX . $m[1] . self::URL_IMG_SUFFIX;
        }

        foreach ($json[Scrape::DATA]['interactionStatistic'] ?? [] as $inter) {
            if (($inter['interactionType'] ?? '') == 'http://schema.org/WatchAction') {
                $r[Scrape::DATA][self::VIEWS] = $inter['userInteractionCount'] ?? '';
            }
        }

        return $r;
    }

}
