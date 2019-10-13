<?php

if (!$img = $app->imageGet($imgSlug, ['w' => $imgW, 'h' => $imgH])) {
    http_response_code(404);
    exit('error');
}
exit('ok');
