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

    public static int $viewportMaxDefault = 1000;
    public static int $viewportMinDefault = 320;



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
            $min = min($size[0], $size[1]);

            return 'max(' . $min . 'px, ' . self::calc($size) . ')';
        } else {
            $max = max($size[0], $size[1]);

            return 'min(' . $max . 'px, ' . self::calc($size) . ')';
        }
    }


    static function minNeg(array $size): string {
        $size[0] = -$size[0];
        $size[1] = -$size[1];

        return self::min($size);
    }



    static function calc(array $size): string {
        $m = self::slope($size[2] ?? self::$viewportMinDefault, $size[0], $size[3] ?? self::$viewportMaxDefault, $size[1]);
        $b = self::yIntersect($size[0], $size[1], $size[2] ?? self::$viewportMinDefault, $size[3] ?? self::$viewportMaxDefault);

        return 'calc(' . round($m * 100, 3, PHP_ROUND_HALF_UP) . 'vw + ' . round($b, 3, PHP_ROUND_HALF_UP) . 'px)';
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
        $alpha = match($alpha) {
            1.0 => '1.0',
            0.0 => '0.0',
            default => round(number_format($alpha, 3, '.', ''), 3)
        };

        return "rgba({$rgb}, {$alpha})";
    }

}
