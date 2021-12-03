<?php

use Galaxia\Flash;
use Galaxia\G;


$auth->logout(G::$req->host);
Flash::cleanMessages();
G::redirect('edit', 303);
