<?php

use Galaxia\G;
use GalaxiaEditor\E;


if (!$img = G::imageGet(E::$imgSlug, ['w' => E::$imgW, 'h' => E::$imgH])) {
    http_response_code(404);
    exit('error');
}
exit('ok');
