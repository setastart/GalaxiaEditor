<?php

namespace GalaxiaEditor\render;


use Galaxia\G;
use GalaxiaEditor\model\ModelImage;


class Load {

    static function imagesInUse(array $geConf, string $pgSlug): array {
        return G::cache('editor', 2, 'imageList-' . $pgSlug . '-inUse', function() use ($geConf, $pgSlug) {
            return ModelImage::inUse($geConf, $pgSlug);
        });
    }

}
