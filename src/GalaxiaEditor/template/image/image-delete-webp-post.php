<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Flash;
use GalaxiaEditor\E;


$count = AppImage::deleteWebp(G::dirImage(), E::$imgSlug);

Flash::info('Deleted webp images: ' . $count);
G::redirect('edit/' . E::$pgSlug . '/' . E::$imgSlug);
