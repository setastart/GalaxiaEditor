<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


use function array_map;
use function str_starts_with;

trait Style {



    static function fontSize(array $size): string {
        return 'font-size: ' . self::px($size) . '; font-size: ' . self::min($size) . ';' . PHP_EOL;
    }



    static function px(array $size): string {
        return $size[1] . 'px';
    }




    /**
     * @param array $size [min, max, wMin, wMax]
     * @return string
     */
    static function min(array $size): string {
        if ($size[0] > $size[1]) {
            return 'max(' . min($size[0], $size[1]) . 'px, ' . self::calc(size: $size, excludeFn: true) . ')';
        } else {
            return 'min(' . max($size[0], $size[1]) . 'px, ' . self::calc(size: $size, excludeFn: true) . ')';
        }
    }


    static function minNeg(array $size): string {
        $size[0] = -$size[0];
        $size[1] = -$size[1];

        return self::min($size);
    }



    static function calc(array $size, bool $excludeFn = false): string {
        $m = self::slope($size[2] ?? Asset::$viewportMinDefault, $size[0], $size[3] ?? Asset::$viewportMaxDefault, $size[1]);
        $b = self::yIntersect($size[0], $size[1], $size[2] ?? Asset::$viewportMinDefault, $size[3] ?? Asset::$viewportMaxDefault);

        if ($excludeFn) {
            return round($m * 100, 3, PHP_ROUND_HALF_UP) . 'vw + ' . round($b, 3, PHP_ROUND_HALF_UP) . 'px';
        } else {
            return 'calc(' . round($m * 100, 3, PHP_ROUND_HALF_UP) . 'vw + ' . round($b, 3, PHP_ROUND_HALF_UP) . 'px)';
        }
    }




    static function slope(float $x1, float $y1, float $x2, float $y2): float {
        return ($y2 - $y1) / ($x2 - $x1);
    }




    /**
     * Get the y-intersect of two points
     * calc slope:       m = (y2 - y1) / (x2 - x1)
     * calc y-intersect: b = y1 - (m * x1)
     */
    static function yIntersect(float $y1, float $y2, float $x1 = 320, float $x2 = 1000, int $round = 3): float {
        $m = ($y2 - $y1) / ($x2 - $x1);

        return round($y1 - ($m * $x1), $round, PHP_ROUND_HALF_UP);
    }



    /**
     * @param string $hex fa0, ffaa00, #fa0 or #ffaa00
     * @return array [$r, $g, $b]
     */
    static function hex2rgbArray(string $hex): array {
        $color = str_starts_with($hex, '#') ? substr($hex, 1) : $hex;

        if (strlen($color) == 6) {
            $hexArray = [$color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]];
        } else if (strlen($color) == 3) {
            $hexArray = [$color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]];
        } else {
            return [0, 0, 0];
        }

        return array_map('hexdec', $hexArray);
    }


    static function rgb2hsl($r, $g, $b): array {
        $r   /= 255;
        $g   /= 255;
        $b   /= 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;
        if ($d == 0) {
            $h = $s = 0;
        } else {
            $h = 0;
            $s = $d / (1 - abs(2 * $l - 1));
            switch ($max) {
                case $r:
                    $h = 60 * fmod((($g - $b) / $d), 6);
                    if ($b > $g) {
                        $h += 360;
                    }
                    break;
                case $g:
                    $h = 60 * (($b - $r) / $d + 2);
                    break;
                case $b:
                    $h = 60 * (($r - $g) / $d + 4);
                    break;
            }
        }
        return [round($h), round($s * 100), round($l * 100)];
    }

    static function hsl2rgb($h, $s, $l): array {
        $c = (1 - abs(2 * ($l / 100) - 1)) * $s / 100;
        $x = $c * (1 - abs(fmod(($h / 60), 2) - 1));
        $m = ($l / 100) - ($c / 2);
        if ($h < 60) {
            $r = $c;
            $g = $x;
            $b = 0;
        } else if ($h < 120) {
            $r = $x;
            $g = $c;
            $b = 0;
        } else if ($h < 180) {
            $r = 0;
            $g = $c;
            $b = $x;
        } else if ($h < 240) {
            $r = 0;
            $g = $x;
            $b = $c;
        } else if ($h < 300) {
            $r = $x;
            $g = 0;
            $b = $c;
        } else {
            $r = $c;
            $g = 0;
            $b = $x;
        }
        return [floor(($r + $m) * 255), floor(($g + $m) * 255), floor(($b + $m) * 255)];
    }



    /**
     * @param string $hex fa0, ffaa00, #fa0 or #ffaa00
     * @param int    $degrees
     * @return string #123456
     */
    static function hueRotate(string $hex, int $degrees): string {
        $hsl = self::rgb2hsl(...self::hex2rgbArray($hex));
        $hsl[0] += $degrees;
        while ($hsl[0] < 0) $hsl[0] += 360;
        while ($hsl[0] > 360) $hsl[0] -= 360;
        $rgb = self::hsl2rgb(...$hsl);
        $rgb = array_map(fn($c) => str_pad(dechex($c), 2, 0, STR_PAD_LEFT), $rgb);
        return "#{$rgb[0]}{$rgb[1]}{$rgb[2]}";
    }


    /**
     * @param string $hex   fa0, ffaa00, #fa0 or #ffaa00
     * @param float  $alpha 0.0 - 1.0
     *
     * @return string rgba(123, 123, 123, 0.5)
     */
    static function hex2rgba(string $hex, float $alpha = 1): string {
        $rgb = implode(', ', self::hex2rgbArray($hex));

        $alpha = round(max(0, min(1, $alpha)), 3);
        $alpha = match ($alpha) {
            1.0     => '1.0',
            0.0     => '0.0',
            default => round(number_format($alpha, 3, '.', ''), 3)
        };

        return "rgba({$rgb}, {$alpha})";
    }



    /**
     * @param string $fg    fa0, ffaa00, #fa0 or #ffaa00
     * @param float  $fgMix 0.0-1.0
     * @param string $bg    fa0, ffaa00, #fa0 or #ffaa00
     *
     * @return string #123456
     */
    static function hexMix(string $fg, float $fgMix = 1, string $bg = 'ffffff'): string {
        $rgb1 = self::hex2rgbArray($fg);
        $rgb2 = self::hex2rgbArray($bg);

        $r = str_pad(dechex(round($rgb1[0] + (($rgb2[0] - $rgb1[0]) * (1.0 - $fgMix)))), 2, '0', STR_PAD_LEFT);
        $g = str_pad(dechex(round($rgb1[1] + (($rgb2[1] - $rgb1[1]) * (1.0 - $fgMix)))), 2, '0', STR_PAD_LEFT);
        $b = str_pad(dechex(round($rgb1[2] + (($rgb2[2] - $rgb1[2]) * (1.0 - $fgMix)))), 2, '0', STR_PAD_LEFT);

        return "#{$r}{$g}{$b}";
    }

}
