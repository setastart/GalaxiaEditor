<?php


use Galaxia\Director;
use Galaxia\Flash;


$app->cacheDelete('editor');

Flash::info('editor caches deleted');

Director::redirect('edit/' . $editor->homeSlug);
