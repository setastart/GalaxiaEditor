<?php
/*
 Copyright 2017-2020 Ino Detelić & Zaloa G. Ramos

  - Licensed under the EUPL, Version 1.2 only (the "Licence");
  - You may not use this work except in compliance with the Licence.

  - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

  - Unless required by applicable law or agreed to in writing, software distributed
    under the Licence is distributed on an "AS IS" basis,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  - See the Licence for the specific language governing permissions and limitations under the Licence.
 */

use Galaxia\Director;
use Galaxia\Flash;
use Galaxia\Text;
use GalaxiaEditor\model\ModelList;


$editor->view = 'list/order';

$firstTable  = key($geConf[$pgSlug]['gcList']['gcSelect']);

$changes = ModelList::order($firstTable, $_POST['order']);

if ($changes) {
    Flash::info('list-order - changed position: ' . Text::h($changes));
} else {
    Flash::warning(Text::t('No changes were made.'));
}

if (isset($_POST['submitAndGoBack'])) Director::redirect('edit/' . $pgSlug);

include __DIR__ . '/list.php';
