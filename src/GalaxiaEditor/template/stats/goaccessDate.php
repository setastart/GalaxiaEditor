<?php
/*
 Copyright 2017-2021 Ino Detelić & Zaloa G. Ramos

  - Licensed under the EUPL, Version 1.2 only (the "Licence");
  - You may not use this work except in compliance with the Licence.

  - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

  - Unless required by applicable law or agreed to in writing, software distributed
    under the Licence is distributed on an "AS IS" basis,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  - See the Licence for the specific language governing permissions and limitations under the Licence.
 */


use Galaxia\Director;
use GalaxiaEditor\stats\Stats;


$stats = Stats::getGoaccessDate($itemDate);

$curDate = date('Y-m-d');
if ($curDate == $itemDate) {
    $stats = Stats::getGoaccessTemp($geConf[$pgSlug]['gcGoaccessLog'] ?? '', $geConf[$pgSlug]['gcGoaccessDir'] ?? '');
}

if (is_null($stats)) Director::errorPage(404, 'Could not load Stats file');


exit($stats);
