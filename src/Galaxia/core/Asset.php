<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


use function in_array;

class Asset {


    public static int $viewportMinDefault = 320;
    public static int $viewportMaxDefault = 1000;

    static function build(
        array  $builds,
        string $publicDir,
        string $publicSubdir,
        string $extBuild,
        array  $buildsMin = [],
    ): void {
        G::timerStart(__CLASS__ . '::' . __FUNCTION__ . ' ' . $publicSubdir);

        $dir    = rtrim($publicDir, '/') . "/$publicSubdir/";
        $dirDev = rtrim($publicDir, '/') . "/dev/$publicSubdir/";

        // delete all development built files
        $fileListDev = glob($dirDev . '*' . $extBuild);
        foreach ($fileListDev as $fileDev) unlink($fileDev);


        foreach ($builds as $buildName => $fileList) {
            $build = '';
            $min = in_array($buildName, $buildsMin);

            // build development files
            foreach ($fileList as $fileName) {
                $sourceName = pathinfo(pathinfo($fileName, PATHINFO_FILENAME), PATHINFO_FILENAME);
                $output     = "${dirDev}${buildName}-${sourceName}${extBuild}";


                ob_start();
                require $fileName;
                $fileContent = ob_get_clean();

                file_put_contents($output, PHP_EOL . $fileContent);
                if ($fileContent) {
                    if (!$min) $build .= Text::commentHeader($sourceName) . PHP_EOL;
                    $build .= $fileContent;
                    if (!$min) $build .= PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL;
                }
            }

            // build file
            file_put_contents($dir . $buildName . $extBuild, $build);
        }

        G::timerStop(__CLASS__ . '::' . __FUNCTION__ . ' ' . $publicSubdir);
    }


    static function linkBuild(
        array  $builds,
        string $buildName,
        string $publicSubdir,
        string $extBuild,
        string $version,
        string $rel = 'stylesheet'
    ): void {
        if (!$builds[$buildName] ?? []) return;

        $links = ["/{$publicSubdir}/{$buildName}{$extBuild}{$version}"];

        if (G::isDevEnv() && G::isDev()) {
            $links = [];
            foreach ($builds[$buildName] as $fileName) {
                $sourceName = pathinfo(pathinfo($fileName, PATHINFO_FILENAME), PATHINFO_FILENAME);
                $links[]    = "/dev/{$publicSubdir}/{$buildName}-{$sourceName}{$extBuild}{$version}";
            }
        }

        foreach ($links as $href) {
// @formatter:off ?>
    <link rel="<?=Text::h($rel)?>" href="<?=Text::h($href)?>"/>
<?php } // @formatter:on
    }




    static function scriptBuild(
        array  $builds,
        string $buildName,
        string $publicSubdir,
        string $extBuild,
        string $version,
        string $attributes = 'async defer',
    ): void {
        if (!$builds[$buildName] ?? []) return;

        $links = ["/{$publicSubdir}/{$buildName}{$extBuild}{$version}"];
        if (G::isDevEnv() && G::isDev()) {
            $links = [];
            foreach ($builds[$buildName] as $fileName) {
                $sourceName = pathinfo(pathinfo($fileName, PATHINFO_FILENAME), PATHINFO_FILENAME);
                $links[]    = Text::h("/dev/{$publicSubdir}/{$buildName}-{$sourceName}{$extBuild}{$version}");
            }
        }

        if ($attributes) $attributes = ' ' . trim(Text::h($attributes));

        foreach ($links as $href) {
// @formatter:off ?>
<script type="text/javascript" src="<?=$href?>"<?=$attributes?>></script>
<?php } // @formatter:on
    }




    static function fontFace(
        array  $fonts,
        string $family,
        bool   $oneLine = true,
    ): string {
        $r = '';

        $eol    = $oneLine ? '' : PHP_EOL;
        $indent = $oneLine ? ' ' : '    ';

        foreach ($fonts[$family] ?? [] as $style => $weights) {
            foreach ($weights as $file => $descriptors) {
                $srces = [];
                foreach ($descriptors['local'] ?? [] as $local) {
                    $srces[] = "local('" . Text::h($local) . "')";
                }
                foreach ($descriptors['ext'] ?? ['woff2'] as $ext) {
                    if ($descriptors['variable'] ?? '') {
                        $srces[] = "url('" . Text::h($file) . "." . Text::h($ext) . "') format('" . Text::h($ext) . " supports variations')";
                    } else {
                        $srces[] = "url('" . Text::h($file) . "." . Text::h($ext) . "') format('" . Text::h($ext) . "')";
                    }
                }
                unset($descriptors['local'], $descriptors['ext'], $descriptors['variable']);

                $r .= '@font-face {' . $eol;
                $r .= $indent . "font-family: '" . Text::h($family) . "';" . $eol;
                $r .= $indent . "font-style: " . Text::h($style) . ";" . $eol;
                foreach ($descriptors as $descriptor => $value) {
                    $r .= $indent . "" . Text::h($descriptor) . ": " . Text::h($value) . ";" . $eol;
                }
                $r .= $indent . "src: " . implode(', ', $srces) . ";" . $eol;
                $r .= "}" . $eol . PHP_EOL;
            }
        }

        return $r;
    }




    static function preloadLink(
        string $href,
        string $as = '',
        string $type = '',
        bool   $crossorigin = false,
        string $srcset = '',
        string $sizes = '',
        string $importance = 'high',
    ): string {
        if (!$href) return '';
        $co = '';

        $href = 'href="' . Text::h($href) . '"';
        if ($as) $as = ' as="' . Text::h($as) . '"';
        if ($type) $type = ' type="' . Text::h($type) . '"';
        if ($crossorigin) $co = ' crossorigin';
        if ($srcset) $srcset = ' imagesrcset="' . Text::h($srcset) . '"';
        if ($sizes) $sizes = ' imagesizes="' . Text::h($sizes) . '"';
        if ($importance) $importance = ' importance="' . Text::h($importance) . '"';

        return "    <link rel=\"preload\" $href$as$type$srcset$sizes$importance$co>" . PHP_EOL;
    }


    static function preloadHeader(
        string $href,
        string $as = '',
        string $type = '',
        bool   $crossorigin = false,
        string $srcset = '',
        string $sizes = '',
        string $importance = 'high',
    ): void {
        if (!$href) return;
        $co = '';

        if ($as) $as = ' as="' . Text::h($as) . '";';
        if ($type) $type = ' type="' . Text::h($type) . '";';
        if ($crossorigin) $co = ' crossorigin';
        if ($srcset) $srcset = ' imagesrcset="' . Text::h($srcset) . '"';
        if ($sizes) $sizes = ' imagesizes="' . Text::h($sizes) . '"';
        if ($importance) $importance = ' importance="' . Text::h($importance) . '"';

        header("link: <$href>; rel=preload;$as$type$srcset$sizes$importance$co", false);
    }

}
