<?php

use Galaxia\Director;


$auth->logout();
cleanMessages();
Director::redirect('edit', 303);
