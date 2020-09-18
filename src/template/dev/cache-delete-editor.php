<?php


$app->cacheDelete('editor');

info('editor caches deleted');

redirect('edit/' . $editor->homeSlug);
