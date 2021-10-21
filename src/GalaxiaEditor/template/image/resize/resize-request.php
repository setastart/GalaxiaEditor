<?php

use Galaxia\G;


if (!$img = G::imageGet($imgSlug, ['w' => $imgW, 'h' => $imgH])) {
    http_response_code(404);
    exit('error');
}
exit('ok');
