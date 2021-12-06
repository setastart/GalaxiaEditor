<?php

use Galaxia\Flash;
use Galaxia\G;
use GalaxiaEditor\E;


E::$auth->logout();
Flash::cleanMessages();
G::redirect('edit', 303);
