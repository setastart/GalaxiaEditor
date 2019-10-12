<?php

$count = gImageDeleteResizes($app->dirImages, $imgSlug);

info('Deleted image resizes: ' . $count);
redirect('edit/' . $pgSlug . '/' . $imgSlug);
