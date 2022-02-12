<?php


namespace GalaxiaEditor\build;


use Galaxia\Text;


class Js {

    const FILE_EXT_JS_BUILD = '.js';

    const JS_FILENAMES = [
        'galaxiaEditor' => [
            __DIR__ . '/js/polyfill.js',
            __DIR__ . '/js/main.js',
            __DIR__ . '/js/filter.js',
            __DIR__ . '/js/image.js',
            __DIR__ . '/js/field.js',
            __DIR__ . '/js/input.js',
            __DIR__ . '/js/scrape.js',
            __DIR__ . '/js/text.js',
            __DIR__ . '/js/translate.js',
        ],

        'galaxiaChat' => [
            __DIR__ . '/js/galaxiaChat.js',
        ],
    ];


    static function build(): void {
        foreach (self::JS_FILENAMES as $buildName => $sources) {
            $jsBuild = '';
            // $jsBuild .= '\'use strict\';' . str_repeat(PHP_EOL, 2);

            foreach ($sources as $sourcePath) {
                $source = pathinfo($sourcePath, PATHINFO_BASENAME);

                $js = file_get_contents($sourcePath);
                if (!$js) continue;

                $jsBuild .= Text::commentHeader($source) . PHP_EOL;
                $jsBuild .= $js;
                $jsBuild .= str_repeat(PHP_EOL, 4);
            }

            $filePathMain = dirname(__DIR__, 3) . '/public/edit/js/' . $buildName . self::FILE_EXT_JS_BUILD;

            $dir = pathinfo($filePathMain, PATHINFO_DIRNAME);
            if (!is_dir($dir)) mkdir($dir, 0644, true);

            file_put_contents($filePathMain, $jsBuild);
        }
    }

}
