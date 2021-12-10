<?php


namespace Galaxia;


use DateTime;


class SchemaOrg {

    static function breadcrumbs(array $pages): array {
        $list = [];
        $i    = 1;
        foreach ($pages as $name => $url) {
            if (!$name || !$url) continue;
            if (!is_string($name) || !is_string($url)) continue;

            $list[] = [
                '@type'    => 'ListItem',
                'position' => $i,
                'name'     => $name,
                'item'     => G::$req->schemeHost() . $url,
            ];
            $i++;
        }

        if (!$list) return [];

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }


    static function images(array $images, $imageCount = 8): array {
        $images = array_slice($images, 0, $imageCount);

        $r = [];
        foreach ($images ?? [] as $img) {
            $r[] = G::$req->schemeHost() . $img['src'];
        }

        return $r;
    }


    static function brand(
        string $name
    ): array {
        return [
            '@type' => 'Brand',
            'name'  => $name,
        ];
    }


    static function product(
        string $name,
        string $desc,
        array $img,
        string $sku,
        string $url,
        int $price,
        string $availability,
        string $brand,
        string $review,
        string $rating
    ): array {
        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'Product',
            'name'            => $name,
            'description'     => $desc,
            'image'           => $img,
            'sku'             => $sku,
            'offers'          => [
                '@type'           => 'Offer',
                'url'             => $url,
                'price'           => $price,
                'priceCurrency'   => 'EUR',
                'availability'    => $availability,
                'priceValidUntil' => date('Y') . '-12-31',
            ],
            $brand,
            'review'          => $review,
            'aggregateRating' => $rating,
        ];
    }


    static function article(
        string $headline,
        DateTime $dtCreate,
        DateTime $dtModify,
        array $images
    ): array {
        return [
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => $headline,
            'image'         => $images,
            'datePublished' => $dtCreate->format(DATE_ATOM),
            'dateModified'  => $dtModify->format(DATE_ATOM),
        ];
    }

}
