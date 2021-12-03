<?php

use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\history\History;


$editor->view = 'item/delete/delete';




// delete item from database

try {
    $query = Sql::delete($item['gcDelete']);

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

foreach ($item['inputs'] as $inputName => $input) {
    History::insert($uniqueId, $item['gcTable'], E::$itemId, $inputName, '', 0, $input['valueFromDb'] ?? '', $me->id);
}

foreach ($modules as $moduleKey => $module) {
    switch ($module['gcModuleType']) {
        case 'fields':
            foreach ($module['inputs'] as $fieldKey => $inputs) {
                foreach ($inputs as $inputKey => $input) {
                    if (!isset($input['valueFromDb'])) continue;
                    History::insert($uniqueId, $item['gcTable'], E::$itemId, $inputKey, $fieldKey, 0, $input['valueFromDb'], $me->id);
                }
            }
            break;
        default:
            geErrorPage(500, 'delete post - invalid module');
            break;
    }
}




// finish

G::cacheDelete('editor');
Flash::info(sprintf(Text::t('Deleted: %s.'), Text::t(E::$section['gcTitleSingle'])));

if (!in_array(E::$pgSlug, ['users', 'passwords'])) {
    G::cacheDelete(['app', 'fastroute']);
    G::routeSitemap(G::$req->schemeHost());
    if (file_exists(G::dir() .'src/script/_editor-item-update-hard.php'))
        include G::dir() .'src/script/_editor-item-update-hard.php';
}

G::redirect('edit/' . E::$pgSlug);
