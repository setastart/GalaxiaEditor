<?php

use Galaxia\Flash;
use Galaxia\G;
use GalaxiaEditor\E;


E::$auth->logout(G::$req->host);
Flash::cleanMessages();
G::redirect('edit', 303);
