<?php
// Copyright 2017-2023 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


use function is_array;
use function ob_start;

class Head {

    static function full(
        AppMeta $meta,
        array   $metaDescription = null,
        int     $metaDescriptionLength = 160,
        string  $metaAuthor = '',
        string  $metaRobots = null,
        string  $metaCanonical = null,

        string  $queryVersion = null,

        string  $css = null,
        array   $cssBuilds = [],
        string  $cssBuildName = 'main',
        string  $cssExtBuild = '.build.css',

        string  $preload = null,

        string  $linkIcon = null,
        string  $color = '',

        array   $langAlt = null,

        string  $ogTitle = null,
        string  $ogType = 'website',
        string  $ogImage = null,
        string  $ogUrl = null,
        array   $ogDesc = null,
        int     $ogDescLength = 300,
        string  $ogLocale = null,

        array   $structuredData = [],
        bool    $structuredDataPretty = false,
    ): string {
        ob_start();

        $desc      = Text::descg(arr: $metaDescription ?? $meta->desc, length: $metaDescriptionLength) ?? '';
        $author    = Text::h($metaAuthor);
        $robots    = Text::h($metaRobots ?? ($meta->index ? 'max-image-preview:large' : 'noindex'));
        $canonical = isset($metaCanonical) ? Text::h($metaCanonical) : (G::$req->schemeHost() . Text::h(G::$req->path) ?? '');

        $version = Text::h($queryVersion ?? $meta->version);

        $css ??= Asset::linkBuild(
            builds: $cssBuilds,
            buildName: $cssBuildName,
            publicSubdir: 'css',
            extBuild: $cssExtBuild,
            version: $version,
            rel: 'stylesheet'
        );

        $preload ??= $meta->preload;

        $linkIcon = Text::h($linkIcon ?? '/favicon.png');

        $jsonLd = '';
        if ($structuredData) {
            $flags  = $structuredDataPretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE : JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
            $jsonLd = json_encode($structuredData, $flags);
        }

// @formatter:off ?>
<head>
<?php   Head::metaFirst(); ?>
    <title><?=Text::h($meta->titleHead)?></title>
<?=$css, PHP_EOL?>
<?=$preload, PHP_EOL?>

    <meta name="description" content="<?=$desc?>">
    <meta name="author" content="<?=$author?>">
    <meta name="robots" content="<?=$robots?>">
<?=$langAlt ?? Head::langAltString($meta->url), PHP_EOL?>
    <link rel="canonical" href="<?=$canonical?>">

    <link rel="icon" href="<?=$linkIcon, $version?>">
    <link rel="apple-touch-icon" href="<?=$linkIcon, $version?>">
<?php   if ($color) { ?>
    <meta name="theme-color" content="<?=Text::h($color)?>">
<?php   } ?>
    <meta property="og:title" content="<?=Text::h($ogTitle ?? $meta->titleHead)?>">
    <meta property="og:type" content="<?=Text::h($ogType)?>">
    <meta property="og:image" content="<?=Text::h($ogImage ?? $meta->ogImage)?>">
    <meta property="og:url" content="<?=Text::h($ogUrl ?? $meta->ogUrl)?>">
    <meta property="og:description" content="<?=Text::descg($ogDesc ?? $meta->desc, null, $ogDescLength)?>">
    <meta property="og:locale" content="<?=Text::h($ogLocale ?? $meta->ogLocale)?>">
<?php   if ($jsonLd) { ?>
    <script type="application/ld+json">
<?=$jsonLd?>
    </script>
<?php   } ?>
</head>
<?php // @formatter:on

        return ob_get_clean();
    }

    static function metaFirst(): void {
// @formatter:off ?>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

<?php } // @formatter:on



    // todo: make $desctription accept only strings
    static function metaSecond(string|array $description, string $author, bool $index, int $length = 160): void {
        if (is_array($description)) {
            $desc = Text::descg($description, null, $length) ?? '';
        } else {
            $desc = Text::desc($description, $length) ?? '';
        }

// @formatter:off ?>

    <meta name="description" content="<?=$desc?>">
    <meta name="author" content="<?=Text::h($author)?>">
<?php   if (!$index) { ?>

    <meta name="robots" content="noindex">
<?php   } ?>
<?php } // @formatter:on


    static function metaSecondError(string $author): void {
// @formatter:off ?>

    <meta name="author" content="<?=Text::h($author)?>">

    <meta name="robots" content="noindex">
<?php } // @formatter:on




    static function langAlt(array $url): void {
        if (count($url) < 2) return;
        echo PHP_EOL;
        foreach (G::locales() as $lang => $_) {
            if (!isset($url[$lang])) continue;
            $langUrl = $url[$lang];
// @formatter:off ?>
    <link rel="alternate" hreflang="<?=$lang?>" href="<?=G::$req->schemeHost() . Text::h($langUrl)?>">
<?php } // @formatter:on
    }


    static function langAltString(array $url): string {
        if (count($url) < 2) return '';

        ob_start();

        echo PHP_EOL;
        foreach (G::locales() as $lang => $_) {
            if (!isset($url[$lang])) continue;
            $langUrl = $url[$lang];
// @formatter:off ?>
    <link rel="alternate" hreflang="<?=$lang?>" href="<?=G::$req->schemeHost() . Text::h($langUrl)?>">
<?php } // @formatter:on

        return ob_get_clean();
    }


    static function prevNext(string $prev = '', string $next = ''): void {
// @formatter:off ?>
<?php   if ($next) { ?>
    <link rel="next" href="<?=G::$req->schemeHost() . Text::h($next)?>">
<?php   } ?>
<?php   if ($prev) { ?>
    <link rel="prev" href="<?=G::$req->schemeHost() . Text::h($prev)?>">
<?php   } ?>
<?php } // @formatter:on




    static function favicon(string $version = ''): void {
        $version = Text::h($version);
// @formatter:off ?>

    <link rel="shortcut icon" href="/favicon.ico<?=$version?>">
    <link rel="apple-touch-icon" href="/favicon.png<?=$version?>">
<?php } // @formatter:on



    // todo: make $desctription accept only strings
    static function openGraph(
        string       $title,
        string       $image,
        string       $url,
        string|array $description,
        string       $locale,
        int          $descLength = 300
    ): void {
        if (is_array($description)) {
            $desc = Text::descg($description, null, $descLength) ?? '';
        } else {
            $desc = Text::desc($description, $descLength) ?? '';
        }

// @formatter:off ?>

    <meta property="og:title" content="<?=Text::h($title)?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?=Text::h($image)?>">
    <meta property="og:url" content="<?=Text::h($url)?>">
    <meta property="og:description" content="<?=$desc?>">
    <meta property="og:locale" content="<?=Text::h($locale)?>">
<?php } // @formatter:on




    static function headSchemaOrg(
        array $schema,
        bool  $prettyPrint = false,
    ): void {
        if (!$schema) return;

        if ($prettyPrint) {
// @formatter:off ?>

    <script type="application/ld+json">
<?=json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), PHP_EOL?>
    </script>
<?php // @formatter:on
        } else {
// @formatter:off ?>

    <script type="application/ld+json">
<?=json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), PHP_EOL?>
    </script>
<?php // @formatter:on
        }
    }


    static function css(
        array  $builds,
        string $buildName,
        string $extBuild,
        string $version
    ): void {
        echo Asset::linkBuild(
            builds: $builds,
            buildName: $buildName,
            publicSubdir: 'css',
            extBuild: $extBuild,
            version: $version,
            rel: 'stylesheet'
        );
    }




    static function cssError(string $buildName, string $extBuild): void {
// @formatter:off ?>

    <link rel="stylesheet" href="/css/<?=Text::h($buildName . $extBuild)?>">
<?php } // @formatter:on




    static function js(
        array  $builds,
        string $buildName,
        string $extBuild,
        string $version
    ): void {
        echo PHP_EOL;
        echo Asset::linkBuild(
            builds: $builds,
            buildName: $buildName,
            publicSubdir: 'js',
            extBuild: $extBuild,
            version: $version,
            rel: 'script'
        );
    }


}
