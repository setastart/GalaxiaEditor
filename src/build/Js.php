<?php


namespace build;


use Galaxia\Editor;


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
        ],
        'galaxiaChat'   => [
            __DIR__ . '/js/galaxiaChat.js',
        ],
    ];


    static function build() {
        $editor = new Editor(dirname(dirname(__DIR__)));

        foreach (self::JS_FILENAMES as $buildName => $sources) {
            $jsBuild = '\'use strict\';' . str_repeat(PHP_EOL, 2);

            foreach ($sources as $sourcePath) {
                $source = pathinfo($sourcePath, PATHINFO_BASENAME);

                $js = file_get_contents($sourcePath);
                if (!$js) continue;

                $jsBuild .= '/********' . str_repeat('*', strlen($source)) . '********/' . PHP_EOL;
                $jsBuild .= '/******  ' . h($source) . '  ******/' . PHP_EOL;
                $jsBuild .= '/********' . str_repeat('*', strlen($source)) . '********/' . str_repeat(PHP_EOL, 2);
                $jsBuild .= $js;
                $jsBuild .= str_repeat(PHP_EOL, 4);
            }

            $filePathMain = $editor->dir . 'public/edit/js/' . $buildName . self::FILE_EXT_JS_BUILD;

            $dir = pathinfo($filePathMain, PATHINFO_DIRNAME);
            if (!is_dir($dir)) mkdir($dir, 0644, true);

            file_put_contents($filePathMain, $jsBuild);
        }

    }

}
