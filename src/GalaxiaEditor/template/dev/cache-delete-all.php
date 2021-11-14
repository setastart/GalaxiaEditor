<?php


use Galaxia\Flash;
use Galaxia\G;


$editor->view = 'dev/dev';


G::cacheDeleteAll();

Flash::info('ALL caches deleted');
