<?php

use Galaxia\G;
use Galaxia\Flash;


$auth->logout();
Flash::cleanMessages();
G::redirect('edit', 303);
