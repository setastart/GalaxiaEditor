<?php


use Galaxia\Flash;
use Galaxia\G;


G::$editor->view = 'dev/dev';


G::cacheDelete(['app', 'fastroute']);

Flash::info('app caches deleted');
