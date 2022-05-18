<?php
/* Copyright 2017-2021 Ino Detelić & Zaloa G. Ramos

 - Licensed under the EUPL, Version 1.2 only (the "Licence");
 - You may not use this work except in compliance with the Licence.

 - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

 - Unless required by applicable law or agreed to in writing, software distributed
   under the Licence is distributed on an "AS IS" basis,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 - See the Licence for the specific language governing permissions and limitations under the Licence.
*/

namespace Galaxia;


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
     * @param string $hex   fa0, ffaa00, #fa0 or #ffaa00
     * @param float  $alpha 0.0 - 1.0
     *
     * @return string rgba(123, 123, 123, 0.5)
     */
    static function hex2rgba(string $hex, float $alpha = 1): string {
        $color = str_starts_with($hex, '#') ? substr($hex, 1) : $hex;

        if (strlen($color) == 6) {
            $hexArray = [$color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]];
        } else if (strlen($color) == 3) {
            $hexArray = [$color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]];
        } else {
            return $hex;
        }

        $rgb = implode(', ', array_map('hexdec', $hexArray));

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
        $color1 = str_starts_with($fg, '#') ? substr($fg, 1) : $fg;

        if (strlen($color1) == 6) {
            $hexArray1 = [$color1[0] . $color1[1], $color1[2] . $color1[3], $color1[4] . $color1[5]];
        } else if (strlen($color1) == 3) {
            $hexArray1 = [$color1[0] . $color1[0], $color1[1] . $color1[1], $color1[2] . $color1[2]];
        } else {
            return $fg;
        }
        $rgb1 = array_map('hexdec', $hexArray1);

        $color2 = str_starts_with($bg, '#') ? substr($bg, 1) : $bg;
        if (strlen($color2) == 6) {
            $hexArray2 = [$color2[0] . $color2[1], $color2[2] . $color2[3], $color2[4] . $color2[5]];
        } else if (strlen($color2) == 3) {
            $hexArray2 = [$color2[0] . $color2[0], $color2[1] . $color2[1], $color2[2] . $color2[2]];
        } else {
            return $fg;
        }
        $rgb2 = array_map('hexdec', $hexArray2);

        $r = str_pad(dechex(round($rgb1[0] + (($rgb2[0] - $rgb1[0]) * (1.0 - $fgMix)))), 2, '0', STR_PAD_LEFT);
        $g = str_pad(dechex(round($rgb1[1] + (($rgb2[1] - $rgb1[1]) * (1.0 - $fgMix)))), 2, '0', STR_PAD_LEFT);
        $b = str_pad(dechex(round($rgb1[2] + (($rgb2[2] - $rgb1[2]) * (1.0 - $fgMix)))), 2, '0', STR_PAD_LEFT);

        return "#{$r}{$g}{$b}";
    }

}
