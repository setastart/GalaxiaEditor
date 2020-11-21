<?php


namespace Galaxia;


trait Style {

    public static int $viewportMaxDefault = 1000;
    public static int $viewportMinDefault = 320;



    static function fontSize(array $size): string {
        return 'font-size: ' . self::px($size) . '; font-size: ' . self::min($size) . ';' . PHP_EOL;
    }




    static function px(array $size): string {
        return $size[1] . 'px';
    }




    static function calc(array $size): string {
        $m = self::slope($size[2] ?? self::$viewportMinDefault, $size[0], $size[3] ?? self::$viewportMaxDefault, $size[1]);
        $b = self::yIntersect($size[0], $size[1], $size[2] ?? self::$viewportMinDefault, $size[3] ?? self::$viewportMaxDefault, 3);

        return 'calc(' . round($m * 100, 3, PHP_ROUND_HALF_UP) . 'vw + ' . round($b, 3, PHP_ROUND_HALF_UP) . 'px)';
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




    static function slope(int $x1, int $y1, int $x2, int $y2): float {
        return ($y2 - $y1) / ($x2 - $x1);
    }




    /**
     * Get the y-intersect of two points
     * calc slope:       m = (y2 - y1) / (x2 - x1)
     * calc y-intersect: b = y1 - (m * x1)
     */
    static function yIntersect(int $y1, int $y2, int $x1 = 320, int $x2 = 1000, int $round = 3): float {
        $m = ($y2 - $y1) / ($x2 - $x1);

        return round($y1 - ($m * $x1), $round, PHP_ROUND_HALF_UP);
    }

}
