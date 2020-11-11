<?php


use Galaxia\Flash;


$editor->view = 'dev/dev';


$app->cacheDeleteAll();

Flash::info('ALL caches deleted');
