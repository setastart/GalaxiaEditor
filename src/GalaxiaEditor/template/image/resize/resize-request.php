<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\G;
use GalaxiaEditor\E;


if (!G::imageGet(E::$imgSlug, ['w' => E::$imgW, 'h' => E::$imgH])) {
    http_response_code(404);
    exit('error');
}
exit('ok');
