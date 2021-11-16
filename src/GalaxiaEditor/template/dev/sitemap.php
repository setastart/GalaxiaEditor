<?php


use Galaxia\G;
use GalaxiaEditor\E;


$editor->view = 'dev/dev';


G::routeSitemap(E::$req->schemeHost());
