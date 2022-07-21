<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


class Head {

    static function metaFirst(): void {
// @formatter:off ?>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

<?php } // @formatter:on




    static function metaSecond(array $description, string $author, bool $index, int $length = 160): void {
// @formatter:off ?>

    <meta name="description" content="<?=Text::descg($description, null, $length)?>">
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
// @formatter:off ?>

<?php   foreach ($url ?? [] as $lang => $langUrl) { ?>
    <link rel="alternate" hreflang="<?=$lang?>" href="<?=G::$req->schemeHost() . Text::h($langUrl)?>"/>
<?php   } ?>
<?php } // @formatter:on




    static function prevNext(string $prev = '', string $next = ''): void {
// @formatter:off ?>
<?php   if ($next) { ?>
    <link rel="next" href="<?=G::$req->schemeHost() . Text::h($next)?>"/>
<?php   } ?>
<?php   if ($prev) { ?>
    <link rel="prev" href="<?=G::$req->schemeHost() . Text::h($prev)?>"/>
<?php   } ?>
<?php } // @formatter:on




    static function favicon(): void {
// @formatter:off ?>

    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/favicon.png">
<?php } // @formatter:on




    static function openGraph(
        string $title,
        string $image,
        string $url,
        array  $description,
        string $locale,
        int    $descLength = 300
    ): void {
// @formatter:off ?>

    <meta property="og:title" content="<?=Text::h($title)?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?=Text::h($image)?>">
    <meta property="og:url" content="<?=Text::h($url)?>">
    <meta property="og:description" content="<?=Text::descg($description, null, $descLength)?>">
    <meta property="og:locale" content="<?=Text::h($locale)?>">
<?php } // @formatter:on




    static function headSchemaOrg(array $schema): void {
        if (!$schema) return;
// @formatter:off ?>

    <script type="application/ld+json"><?=PHP_EOL, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), PHP_EOL, '    '?></script>
<?php // @formatter:on
    }


    static function css(
        array  $builds,
        string $buildName,
        string $extBuild,
        string $version
    ): void {
        echo PHP_EOL;
        Asset::linkBuild(
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

    <link rel="stylesheet" href="/css/<?=Text::h($buildName . $extBuild)?>"/>
<?php } // @formatter:on




    static function js(
        array  $builds,
        string $buildName,
        string $extBuild,
        string $version
    ): void {
        echo PHP_EOL;
        Asset::linkBuild(
            builds: $builds,
            buildName: $buildName,
            publicSubdir: 'js',
            extBuild: $extBuild,
            version: $version,
            rel: 'script'
        );
    }


}
