<?php


use Galaxia\Flash;


$editor->view = 'dev/dev';


$app->cacheDelete(['app', 'fastroute']);

Flash::info('app caches deleted');
