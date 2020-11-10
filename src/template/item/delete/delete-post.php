<?php

use Galaxia\Director;
use Galaxia\Sql;


$editor->view = 'item/delete/delete';




// delete item from database

try {
    $query = Sql::delete($item['gcDelete']);

    $stmt = $db->prepare($query);
    $stmt->bind_param('s', $itemId);
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    if ($affectedRows < 1) {
        error('delete-post - Unable to delete from database.');
        return;
    }
} catch (Exception $e) {
    error('delete-post - Unable to delete item.');
    error($e->getMessage());
    return;
}




// history

foreach ($item['inputs'] as $inputName => $input) {
    insertHistory($uniqueId, $item['gcTable'], $itemId, $inputName, '', 0, $input['valueFromDb'] ?? '', $me->id);
}

foreach ($modules as $moduleKey => $module) {
    switch ($module['gcModuleType']) {
        case 'fields':
            foreach ($module['inputs'] as $fieldKey => $inputs) {
                foreach ($inputs as $inputKey => $input) {
                    if (!isset($input['valueFromDb'])) continue;
                    insertHistory($uniqueId, $item['gcTable'], $itemId, $inputKey, $fieldKey, 0, $input['valueFromDb'], $me->id);
                }
            }
            break;
        default:
            geErrorPage(500, 'delete post - invalid module');
            break;
    }
}




// finish

$app->cacheDelete('editor');
info(sprintf(t('Deleted: %s.'), t($geConf[$pgSlug]['gcTitleSingle'])));

if (!in_array($pgSlug, ['users', 'passwords'])) {
    $app->cacheDelete(['app', 'fastroute']);
    $app->generateSitemap($db);
    if (file_exists($app->dir .'src/script/_editor-item-update-hard.php'))
        include $app->dir .'src/script/_editor-item-update-hard.php';
}

Director::redirect('edit/' . $pgSlug);
