<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\Flash;
use Galaxia\G;


G::cacheDelete('editor');

Flash::info('editor caches deleted');

G::redirect('edit/' . G::$editor->homeSlug);
