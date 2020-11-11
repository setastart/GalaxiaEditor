<?php

use Galaxia\Director;
use Galaxia\Flash;


$auth->logout();
Flash::cleanMessages();
Director::redirect('edit', 303);
