<?php

namespace GalaxiaEditor\render;


use GalaxiaEditor\Cache;
use GalaxiaEditor\model\ModelImage;


class Load {

    static function imagesInUse(): array {
        return Cache::imageListInUse(function() {
            return ModelImage::inUse();
        });
    }

}
