<?php


use Galaxia\G;
use Galaxia\Flash;


$app->cacheDelete('editor');

Flash::info('editor caches deleted');

G::redirect('edit/' . $editor->homeSlug);
