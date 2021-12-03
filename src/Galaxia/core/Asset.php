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
        array $builds,
        string $path,
        string $subdir,
        string $srcExt,
        string $destExt
    ): void {
        G::timerStart(__CLASS__ . '::' . __FUNCTION__ . ' ' . $subdir);

        $dir    = rtrim($path, '/') . '/' . $subdir . '/';
        $dirDev = rtrim($path, '/') . '/dev/' . $subdir . '/';

        // delete all development built files
        $fileListDev = glob($dirDev . '*' . $destExt);
        foreach ($fileListDev as $fileDev) unlink($fileDev);


        foreach ($builds as $buildName => $fileList) {
            $build = '';

            // build development files
            foreach ($fileList as $fileName) {
                $sourceName = substr(pathinfo($fileName, PATHINFO_BASENAME), 0, -strlen($srcExt));
                $output     = $dirDev . '/' . $buildName . '-' . $sourceName . $destExt;


                ob_start();
                /** @noinspection PhpIncludeInspection */
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
            file_put_contents($dir . $buildName . $destExt, $build);
        }

        G::timerStop(__CLASS__ . '::' . __FUNCTION__ . ' ' . $subdir);
    }


    static function linkBuild(
        array $builds,
        string $buildName,
        string $subdir,
        string $srcExt,
        string $destExt,
        string $version,
        $rel = 'stylesheet'
    ): void {
        if (!$builds[$buildName] ?? []) return;

        $links = ["/$subdir/$buildName$destExt$version"];

        if (G::isDevEnv() && G::isDev()) {
            $links = [];
            foreach ($builds[$buildName] as $fileName) {
                $sourceName = substr(pathinfo($fileName, PATHINFO_BASENAME), 0, -strlen($srcExt));
                $links[]    = "/dev/$subdir/$buildName-$sourceName$destExt$version";
            }
        }

        foreach ($links as $href) {
// @formatter:off ?>
    <link rel="<?=Text::h($rel)?>" href="<?=Text::h($href)?>"/>
<?php } // @formatter:on
    }



    private static function preloadLink(
        string $href,
        string $as = '',
        string $type = '',
        bool $crossorigin = false,
        string $srcset = '',
        string $sizes = '',
        string $importance = 'high'
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


    private static function preloadHeader(
        string $href,
        string $as = '',
        string $type = '',
        bool $crossorigin = false
    ): void {
        if (!$href) return;
        $co = '';

        if ($as) $as = ' as="' . Text::h($as) . '";';
        if ($type) $type = ' type="' . Text::h($type) . '";';
        if ($crossorigin) $co = ' crossorigin';

        header("link: <$href>; rel=preload;$as$type$co", false);
    }

}
