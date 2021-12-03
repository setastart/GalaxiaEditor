<?php

namespace GalaxiaEditor\render;


use Galaxia\G;
use GalaxiaEditor\E;
use GalaxiaEditor\model\ModelImage;


class Load {

    static function imagesInUse(): array {
        return G::cache('editor', 2, 'imageList-' . E::$pgSlug . '-inUse', function() {
            return ModelImage::inUse();
        }, G::$req->cacheBypass);
    }

}
