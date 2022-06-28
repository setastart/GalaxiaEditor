<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


use DateTime;


class SchemaOrg {

    static function itemList(
        array  $items,
        string $type = 'ItemList'
    ): array {
        $list = [];
        $i    = 1;
        foreach ($items as $item) {
            if (!is_array($item)) continue;

            $list[] = [
                '@type'    => 'ListItem',
                'position' => $i,
                'item'     => $item,
            ];
            $i++;
        }

        if (!$list) return [];

        return [
            '@context'        => 'https://schema.org',
            '@type'           => $type,
            'itemListElement' => $list,
        ];
    }


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
        array  $img,
        string $sku,
        string $url,
        int    $price,
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


    static function organization(
        string $name,
        string $url,
        string $logo,
        string $type = 'Organization'
    ): array {
        return [
            '@context' => 'https://schema.org',
            '@type'    => $type,
            'name'     => $name,
            'url'      => $url,
            'logo'     => $logo,
        ];
    }


    static function review(
        string $rating,
        string $author,
        string $body,
    ): array {
        return [
            '@type'        => 'Review',
            'reviewRating' => [
                '@type'       => 'Rating',
                'ratingValue' => $rating,
            ],
            'author'       => [
                '@type' => 'Person',
                'name'  => $author,
            ],
            'reviewBody'   => $body,
        ];
    }


    static function aggregateRating(
        string $value,
        string $count,
        int    $best = 5,
        int    $worst = 1,
    ): array {
        return [
            '@type'       => 'AggregateRating',
            'ratingValue' => $value,
            'bestRating'  => $best,
            'worstRating' => $worst,
            'ratingCount' => $count,
        ];
    }


    static function course(
        string $name,
        string $desc,
        string $url,
        array  $organization,
        array  $review = [],
        array  $aggregateRating = [],
    ): array {
        $r = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Course',
            'name'        => $name,
            'url'         => $url,
            'description' => $desc,
            'provider'    => $organization,
        ];

        if ($review) {
            $r['review'] = $review;
            if ($aggregateRating) {
                $r['aggregateRating'] = $aggregateRating;
            }
        }

        return $r;
    }


    static function article(
        string   $headline,
        DateTime $dtCreate,
        DateTime $dtModify,
        array    $images
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
