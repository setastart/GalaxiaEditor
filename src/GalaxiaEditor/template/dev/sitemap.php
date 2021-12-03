<?php


use Galaxia\G;


G::$editor->view = 'dev/dev';


G::routeSitemap(G::$req->schemeHost());
