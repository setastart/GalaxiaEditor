<?php


namespace Galaxia;


class Box {

    use Style;

    const with    = 'with';
    const fix     = 'fix';
    const permute = 'permute';
    const negate  = 'negate';

    const borders = [
        'b'  => ['border-width'],
        'bh' => ['border-left-width', 'border-right-width'],
        'bv' => ['border-top-width', 'border-bottom-width'],
        'bt' => ['border-top-width'],
        'br' => ['border-right-width'],
        'bb' => ['border-bottom-width'],
        'bl' => ['border-left-width'],
    ];

    const margins = [
        'm'  => ['margin'],
        'mh' => ['margin-left', 'margin-right'],
        'mv' => ['margin-top', 'margin-bottom'],
        'mt' => ['margin-top'],
        'mr' => ['margin-right'],
        'mb' => ['margin-bottom'],
        'ml' => ['margin-left'],
    ];

    const paddings = [
        'p'  => ['padding'],
        'ph' => ['padding-left', 'padding-right'],
        'pv' => ['padding-top', 'padding-bottom'],
        'pt' => ['padding-top'],
        'pr' => ['padding-right'],
        'pb' => ['padding-bottom'],
        'pl' => ['padding-left'],
    ];

    const dimensions = [
        'w'    => ['width'],
        'wmin' => ['min-width'],
        'wmax' => ['max-width'],
        'h'    => ['height'],
        'hmin' => ['min-height'],
        'hmax' => ['max-height'],
        'mh'   => ['margin-left: auto; margin-right: auto; max-width'],
    ];

    const s1  = [1, 3];
    const s2  = [2, 6];
    const s3  = [3, 9];
    const s4  = [4, 12];
    const s6  = [6, 18];
    const s8  = [8, 24];
    const s12 = [12, 36];
    const s16 = [16, 48];
    const s24 = [24, 72];
    const s32 = [32, 96];
    const s48 = [48, 144];
    const s64 = [64, 192];
    const s96 = [96, 288];

    const templates = [
        'border' => [
            self::with => self::borders,
            self::fix  => ['1px', '2px', '0'],
        ],
        'pad'    => [
            self::with    => self::paddings,
            self::fix     => ['1', '2', '3', '4', '6', '8', '12', '16', '24', '32', '48', '64', '96', '0'],
            self::permute => ['1', '2', '3', '4', '6', '8', '12', '16', '24', '32', '48', '64', '96'],
            self::negate  => ['1', '2', '3', '4', '6', '8', '12', '16', '24', '32', '48', '64', '96'],
        ],
        'mar'    => [
            self::with    => self::margins,
            self::fix     => ['1', '2', '3', '4', '6', '8', '12', '16', '24', '32', '48', '64', '96', 'auto', '0'],
            self::permute => ['1', '2', '3', '4', '6', '8', '12', '16', '24', '32', '48', '64', '96'],
            self::negate  => ['1', '2', '3', '4', '6', '8', '12', '16', '24', '32', '48', '64', '96'],
        ],
    ];

    const sizes = [
        'auto' => 'auto',
        '0'    => '0',
        '1px'  => '1px',
        '2px'  => '2px',

        '1'  => self::s1,
        '2'  => self::s2,
        '3'  => self::s3,
        '4'  => self::s4,
        '6'  => self::s6,
        '8'  => self::s8,
        '12' => self::s12,
        '16' => self::s16,
        '24' => self::s24,
        '32' => self::s32,
        '48' => self::s48,
        '64' => self::s64,
        '96' => self::s96,
    ];


    static function build(
        string $cssDir,
        string $htmlDir,
        string $htmlGlob = '{/,/*/,/*/*/}*.php',
        string $buildName = 'gen-box',
        array  $templates = self::templates,
        array  $sizes = self::sizes
    ): string {
        G::timerStart(__CLASS__ . '::' . __FUNCTION__);

        $cssDir  = rtrim($cssDir, '/');
        $htmlDir = rtrim($htmlDir, '/');

        $searchFiles = glob("$htmlDir$htmlGlob", GLOB_BRACE | GLOB_NOSORT);

        $rules = [];

        foreach ($templates as $templateName => $template) {
            foreach ($template[self::with] as $prefix => $properties) {
                foreach ($template[self::fix] ?? [] as $sizeId) {
                    $root         = "$prefix-$sizeId";
                    $rules[$root] = '';
                    foreach ($properties as $property) {
                        $size = $sizes[$sizeId];
                        if (is_array($size)) {
                            $rules[$root] .= "$property: {$size[0]}px; ";
                            $size = self::min($size);
                        }
                        $rules[$root] .= "$property: {$size}; ";
                    }
                }
                foreach ($template[self::negate] ?? [] as $sizeId) {
                    $root         = "$prefix--$sizeId";
                    $rules[$root] = '';
                    foreach ($properties as $property) {
                        $size = $sizes[$sizeId];
                        if (is_array($size)) {
                            $rules[$root] .= "$property: -{$size[0]}px; ";
                            $size = self::minNeg($size);
                        }
                        $rules[$root] .= "$property: {$size}; ";
                    }
                }
                foreach ($template[self::permute] ?? [] as $sizeIdMin) {
                    foreach ($template[self::permute] as $sizeIdMax) {
                        if ($sizeIdMin == $sizeIdMax) continue;
                        $root         = "$prefix-$sizeIdMin-$sizeIdMax";
                        $rules[$root] = '';
                        foreach ($properties as $property) {
                            $size = [$sizes[$sizeIdMin][0], $sizes[$sizeIdMax][1]];
                            if (is_array($size)) {
                                $rules[$root] .= "$property: {$size[0]}px; ";
                                $size = self::min($size);
                            }
                            $rules[$root] .= "$property: {$size}; ";
                        }
                    }
                }
            }

        }

        $css       = '';
        $cssUnused = '';
        foreach ($rules as $rule => $properties) {
            if (!$properties) continue;

            $found = false;
            foreach ($searchFiles as $file) {
                if (preg_match('~["\' ]' . $rule . '["\' ]~m', file_get_contents($file))) {
                    $css   .= '.' . $rule . ' { ' . $properties . '}' . PHP_EOL;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $cssUnused .= '.' . $rule . ' { ' . $properties . '}' . PHP_EOL;
            }
        }

        file_put_contents("$cssDir/$buildName.css", $css);
        file_put_contents("$cssDir/$buildName-unused.css", $cssUnused);

        G::timerStop(__CLASS__ . '::' . __FUNCTION__);

        // if (G::isDevEnv()) return $css . Text::commentHeader('Unused') . $cssUnused;

        return $css;
    }


}
