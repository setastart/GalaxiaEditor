<?php


namespace GalaxiaEditor\build;


use Galaxia\Editor;
use Galaxia\Text;


class Js {

    const FILE_EXT_JS_BUILD = '.js';

    const JS_FILENAMES = [
    ];


    static function build() {
        $editor = new Editor(dirname(dirname(dirname(__DIR__))));

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

            $filePathMain = $editor->dir . 'public/edit/js/' . $buildName . self::FILE_EXT_JS_BUILD;

            $dir = pathinfo($filePathMain, PATHINFO_DIRNAME);
            if (!is_dir($dir)) mkdir($dir, 0644, true);

            file_put_contents($filePathMain, $jsBuild);
        }

    }

}
