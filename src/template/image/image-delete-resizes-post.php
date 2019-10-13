<?php

$count = gImageDeleteResizes($app->dirImage, $imgSlug);

info('Deleted image resizes: ' . $count);
redirect('edit/' . $pgSlug . '/' . $imgSlug);
