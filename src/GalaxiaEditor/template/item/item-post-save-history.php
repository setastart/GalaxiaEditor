<?php

// insert item history
use Galaxia\Director;
use Galaxia\Flash;
use GalaxiaEditor\history\History;


foreach ($item['inputs'] as $inputName => $input) {
    History::insert($uniqueId, $item['gcTable'], $itemId, $inputName, '', 1, $input['valueFromDb'], $me->id);
}

// insert module history
foreach ($modules as $moduleKey => $module) {
    switch ($module['gcModuleType']) {
        case 'fields':
            foreach ($module['inputs'] as $fieldKey => $inputs) {
                foreach ($inputs as $inputKey => $input) {
                    if (!isset($input['valueFromDb'])) continue;
                    History::insert($uniqueId, $item['gcTable'], $itemId, $inputKey, $fieldKey, 1, $input['valueFromDb'], $me->id);
                }
            }
            break;
        default:
            geErrorPage(500, 'delete post - invalid module');
            break;
    }
}
Flash::info('Saved in History: ' . $geConf[$pgSlug]['gcTitleSingle']);

Director::redirect('edit/' . $pgSlug);
