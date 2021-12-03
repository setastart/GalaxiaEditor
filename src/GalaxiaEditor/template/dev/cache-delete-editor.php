<?php


use Galaxia\Flash;
use Galaxia\G;


G::cacheDelete('editor');

Flash::info('editor caches deleted');

G::redirect('edit/' . $editor->homeSlug);
