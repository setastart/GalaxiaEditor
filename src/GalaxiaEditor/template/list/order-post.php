<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\model\ModelList;


G::$editor->view = 'list/order';

$firstTable  = key(E::$section['gcList']['gcSelect']);

$changes = ModelList::order($firstTable, $_POST['order']);

if ($changes) {
    Flash::info('list-order - changed position: ' . Text::h($changes));
} else {
    Flash::warning(Text::t('No changes were made.'));
}

G::cacheDelete(['app', 'fastroute']);
G::cacheDelete('editor');

Flash::info('editor caches deleted');

if (isset($_POST['submitAndGoBack'])) G::redirect('edit/' . E::$pgSlug);

include __DIR__ . '/list.php';
