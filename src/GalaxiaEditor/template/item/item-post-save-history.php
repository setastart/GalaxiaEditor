<?php
// Copyright 2017-2023 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

// insert item history
use Galaxia\Flash;
use Galaxia\G;
use GalaxiaEditor\E;
use GalaxiaEditor\history\History;


foreach (E::$item['inputs'] as $inputName => $input) {
    History::insert(E::$uniqueId, E::$item['gcTable'], E::$itemId, $inputName, '', 1, $input['valueFromDb'], G::$me->id);
}

// insert module history
foreach (E::$modules as $module) {
    switch ($module['gcModuleType']) {
        case 'fields':
            foreach ($module['inputs'] as $fieldKey => $inputs) {
                foreach ($inputs as $inputKey => $input) {
                    if (!isset($input['valueFromDb'])) continue;
                    History::insert(E::$uniqueId, E::$item['gcTable'], E::$itemId, $inputKey, $fieldKey, 1, $input['valueFromDb'], G::$me->id);
                }
            }
            break;
        default:
            G::errorPage(500, 'delete post - invalid module');
            break;
    }
}
Flash::info('Saved in History: ' . E::$section['gcTitleSingle']);

G::redirect('edit/' . E::$pgSlug);
