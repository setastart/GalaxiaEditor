<?php

$editor->view = 'item/delete/delete';




// delete item from database

try {
    $query = queryDelete($item['gcDelete']);

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
            errorPage(500, 'delete post - invalid module');
            break;
    }
}




// finish

$app->cacheDelete('editor', 'list', $pgSlug);
info(sprintf(t('Deleted: %s.'), t($geConf[$pgSlug]['gcTitleSingle'])));

if (!in_array($pgSlug, ['users', 'passwords'])) {
    $app->cacheDelete(['app', 'fastroute']);
    $app->generateSitemap($db);
    if (file_exists($app->dir .'scripts/_runOnUpdate.php'))
        include $app->dir .'scripts/_runOnUpdate.php';
}

redirect('edit/' . $pgSlug);
