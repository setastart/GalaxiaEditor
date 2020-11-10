<?php


use Galaxia\Director;


$app->cacheDelete('editor');

info('editor caches deleted');

Director::redirect('edit/' . $editor->homeSlug);
