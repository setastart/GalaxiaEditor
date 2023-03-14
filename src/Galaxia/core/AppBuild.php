<?php
// Copyright 2017-2023 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;

class AppBuild {

    static function errorPages(
        string $siteHost = '',
        array  $cssBuilds = [],
        string $cssBuild = '',
        string $cssExtBuild = '.build.css',
        string $cssVersion = ''
    ): void {
        AppTimer::start(__CLASS__ . '::' . __FUNCTION__);

        ob_start();

        $errors = [
            403 => 'Forbidden access',
            404 => 'Page not found',
            500 => 'Internal server error',
            503 => 'Service unavailable',
        ];

        foreach ($errors as $code => $msg) {
            $title = "Error $code";
            if ($cssVersion) $cssVersion = '?v=' . $cssVersion;

            ob_start();

// @formatter:off ?>
<!DOCTYPE html>
<html lang="<?=Text::h(G::lang())?>" prefix="og: https://ogp.me/ns#">
<head>
<?php   Head::metaFirst(); ?>
    <title><?=$title?> - <?=$siteHost?></title>
    <link rel="icon" type="image/png" href="/favicon.png<?=$cssVersion?>">
<?php   Head::css($cssBuilds, $cssBuild, $cssExtBuild, $cssVersion); ?>
</head>
<body class="layout-error">
    <div>
        <h1><?=$title?></h1>
        <h2><?=$msg?></h2>
        <a href="/">
            <img src="/favicon.png<?=$cssVersion?>" width="256" height="256" alt="Logo">
            <span><?=$siteHost?></span>
        </a>
    </div>
</body>
</html>
<?php // @formatter:on

            $render = ob_get_clean();

            file_put_contents(G::dir() . 'public/' . $code . '.html', $render);
        }

        ob_get_clean();

        AppTimer::stop(__CLASS__ . '::' . __FUNCTION__);
    }


    static function css(
        array  $builds,
        array  $min = [],
    ): void {
        Asset::build(
            builds: $builds,
            publicDir: G::dir() . 'public',
            publicSubdir: 'css',
            extBuild: '.build.css',
            buildsMin: $min
        );
    }


    static function js(
        array  $builds,
    ): void {
        Asset::build(
            builds: $builds,
            publicDir: G::dir() . 'public',
            publicSubdir: 'js',
            extBuild: '.build.js'
        );
    }


    static function robots(): void {
        $robots = '';
        $robots .= 'User-agent: *' . PHP_EOL;
        $robots .= 'Disallow:' . PHP_EOL;
        $robots .= PHP_EOL;

        foreach (G::$app->locales as $locale) {
            $url = substr($locale['url'], 1);
            if (!$url) {
                $robots .= 'Sitemap: ' . G::$req->schemeHost() . '/sitemap.xml' . PHP_EOL;
                continue;
            }

            $robots .= 'Sitemap: ' . G::$req->schemeHost() . '/sitemap_' . $url . '.xml' . PHP_EOL;
        }

        file_put_contents(G::dir() . 'public/robots.txt', $robots);
    }


    static function gen(string $namespace): void {
        AppTimer::start(__CLASS__ . '::' . __FUNCTION__);
        // delete all generated files
        $dirOut      = G::dir() . 'gen/';
        $fileListGen = glob($dirOut . '*.php');
        foreach ($fileListGen as $fileGen) unlink($fileGen);

        AppBuild::classes($namespace);
        AppBuild::autoload($namespace);
        AppTimer::stop(__CLASS__ . '::' . __FUNCTION__);
    }


    static function classes(string $namespace): void {
        AppTimer::start(__CLASS__ . '::' . __FUNCTION__);
        $dirIn  = G::dir() . 'src/db/';
        $dirOut = G::dir() . 'gen/';

        $gen      = '';
        $fileList = glob($dirIn . '*.php');
        foreach ($fileList as $fileName) {
            $gen .= file_get_contents($fileName);
        }
        $gen = str_replace('<?php', '', $gen);
        $gen = str_replace("namespace {$namespace}\\db;", '', $gen);

        $lines = explode(PHP_EOL, $gen);
        // dd($lines);

        $uses = [];
        foreach ($lines as $lineId => $line) {
            if (!preg_match('/^use \S+;$/', $line)) continue;

            $uses[$line] = true;
            unset($lines[$lineId]);
        }
        $uses = array_keys($uses);

        $final = '<?php' . PHP_EOL . PHP_EOL . "namespace {$namespace}\db;" . PHP_EOL . PHP_EOL . implode(PHP_EOL, $uses) . PHP_EOL . implode(PHP_EOL, $lines);
        $final = preg_replace('/\n{3,}/', PHP_EOL . PHP_EOL . PHP_EOL, $final);

        file_put_contents($dirOut . 'db.php', $final);
        AppTimer::stop(__CLASS__ . '::' . __FUNCTION__);
    }


    static function autoload(string $namespace): void {
        AppTimer::start(__CLASS__ . '::' . __FUNCTION__);
        $phpGlob     = G::dir() . 'src{/,/*/,/*/*/}*.php';
        $searchFiles = glob($phpGlob, GLOB_BRACE | GLOB_NOSORT);
        $dirOut      = G::dir() . 'gen/';

        $trim = strlen(G::dir() . 'src');

        $reject = [
            '/css/inc/',
            '/js/inc/',
        ];

        $redirect = [
            '/db/' => '/gen/db.php',
        ];

        $load = [];
        foreach ($searchFiles as $file) {

            $file = substr($file, $trim);

            foreach ($reject as $str) {
                if (str_starts_with($file, $str)) continue 2;
            }

            $className = $namespace . '\\' . substr($file, 1, -4);
            $className = str_replace('/', '\\', $className);

            $redirectFound = false;
            foreach ($redirect as $old => $new) {
                if (str_starts_with($file, $old)) {
                    $file          = $new;
                    $redirectFound = true;
                    break;
                }
            }

            if (!$redirectFound) $file = '/src' . $file;

            $load[$className] = $file;
        }

        file_put_contents($dirOut . 'classes.php', '<?php return ' . var_export($load, true) . ';' . PHP_EOL);
        AppTimer::stop(__CLASS__ . '::' . __FUNCTION__);
    }

}
