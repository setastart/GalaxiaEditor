<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\history\History;


G::$editor->view = 'item/delete/delete';




// delete item from database

try {
    $query = Sql::delete(E::$item['gcDelete']);

    $stmt = G::prepare($query);
    $stmt->bind_param('s', E::$itemId);
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    if ($affectedRows < 1) {
        Flash::error('delete-post - Unable to delete from database.');
        return;
    }
} catch (Exception $e) {
    Flash::error('delete-post - Unable to delete item.');
    Flash::error($e->getMessage());
    return;
}




// history

foreach (E::$item['inputs'] as $inputName => $input) {
    History::insert(E::$uniqueId, E::$item['gcTable'], E::$itemId, $inputName, '', 0, $input['valueFromDb'] ?? '', G::$me->id);
}

foreach (E::$modules as $module) {
    switch ($module['gcModuleType']) {
        case 'fields':
            foreach ($module['inputs'] as $fieldKey => $inputs) {
                foreach ($inputs as $inputKey => $input) {
                    if (!isset($input['valueFromDb'])) continue;
                    History::insert(E::$uniqueId, E::$item['gcTable'], E::$itemId, $inputKey, $fieldKey, 0, $input['valueFromDb'], G::$me->id);
                }
            }
            break;
        default:
            G::errorPage(500, 'delete post - invalid module');
            break;
    }
}




// finish

G::cacheDelete('editor');
Flash::info(sprintf(Text::t('Deleted: %s.'), Text::t(E::$section['gcTitleSingle'])));

if (!in_array(E::$pgSlug, ['users', 'passwords'])) {
    G::cacheDelete(['app', 'fastroute']);
    G::routeSitemap(G::$req->schemeHost());
    if (file_exists(G::dir() .'script/_editor-item-update-hard.php'))
        include G::dir() .'script/_editor-item-update-hard.php';
}

G::redirect('edit/' . E::$pgSlug);
