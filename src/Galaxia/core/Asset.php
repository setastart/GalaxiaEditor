<?php
/**
 * Copyright 2017-2021 Ino Detelić & Zaloa G. Ramos
 *
 * - Licensed under the EUPL, Version 1.2 only (the "Licence");
 * - You may not use this work except in compliance with the Licence.
 *
 * - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12
 *
 * - Unless required by applicable law or agreed to in writing, software distributed
 * under the Licence is distributed on an "AS IS" basis,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * - See the Licence for the specific language governing permissions and limitations under the Licence.
 */

namespace Galaxia;


class Asset {


    static function build(
        array  $builds,
        string $publicDir,
        string $publicSubdir,
        string $extSource,
        string $extBuild
    ): void {
        G::timerStart(__CLASS__ . '::' . __FUNCTION__ . ' ' . $publicSubdir);

        $dir    = rtrim($publicDir, '/') . "/$publicSubdir/";
        $dirDev = rtrim($publicDir, '/') . "/dev/$publicSubdir/";

        // delete all development built files
        $fileListDev = glob($dirDev . '*' . $extBuild);
        foreach ($fileListDev as $fileDev) unlink($fileDev);


        foreach ($builds as $buildName => $fileList) {
            $build = '';

            // build development files
            foreach ($fileList as $fileName) {
                $sourceName = substr(pathinfo($fileName, PATHINFO_BASENAME), 0, -strlen($extSource));
                $output     = "${dirDev}${buildName}-${sourceName}${extBuild}";


                ob_start();
                require $fileName;
                $fileContent = ob_get_clean();

                file_put_contents($output, PHP_EOL . $fileContent);
                if ($fileContent) {
                    $build .= Text::commentHeader($sourceName) . PHP_EOL;
                    $build .= $fileContent;
                    $build .= PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL;
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
        string $extSource,
        string $extBuild,
        string $version,
        string $rel = 'stylesheet'
    ): void {
        if (!$builds[$buildName] ?? []) return;

        $links = ["/{$publicSubdir}/{$buildName}{$extBuild}{$version}"];

        if (G::isDevEnv() && G::isDev()) {
            $links = [];
            foreach ($builds[$buildName] as $fileName) {
                $sourceName = substr(pathinfo($fileName, PATHINFO_BASENAME), 0, -strlen($extSource));
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
        string $extSource,
        string $extBuild,
        string $version,
        string $attributes = 'async defer',
    ): void {
        if (!$builds[$buildName] ?? []) return;

        $links = ["/{$publicSubdir}/{$buildName}{$extBuild}{$version}"];
        if (G::isDevEnv() && G::isDev()) {
            $links = [];
            foreach ($builds[$buildName] as $fileName) {
                $sourceName = substr(pathinfo($fileName, PATHINFO_BASENAME), 0, -strlen($extSource));
                $links[]    = Text::h("/dev/{$publicSubdir}/{$buildName}-{$sourceName}{$extBuild}{$version}");
            }
        }

        if ($attributes) $attributes = ' ' . trim(Text::h($attributes));

        foreach ($links as $href) {
// @formatter:off ?>
<script type="text/javascript" src="<?=$href?>"<?=$attributes?>></script>
<?php } // @formatter:on
    }




    static function fontFace(array $fonts, string $family): string {
        $r = '';
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

                $r .= '@font-face {' . PHP_EOL;
                $r .= "    font-family: '" . Text::h($family) . "';" . PHP_EOL;
                $r .= "    font-style: " . Text::h($style) . ";" . PHP_EOL;
                foreach ($descriptors as $descriptor => $value) {
                    $r .= "    " . Text::h($descriptor) . ": " . Text::h($value) . ";" . PHP_EOL;
                }
                $r .= "    src: " . implode(', ', $srces) . ";" . PHP_EOL;
                $r .= "}" . PHP_EOL . PHP_EOL;
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
