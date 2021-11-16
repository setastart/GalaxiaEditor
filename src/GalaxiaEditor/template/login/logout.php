<?php

use Galaxia\G;
use Galaxia\Flash;
use GalaxiaEditor\E;


$auth->logout(E::$req->host);
Flash::cleanMessages();
G::redirect('edit', 303);
