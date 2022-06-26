<?php

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
