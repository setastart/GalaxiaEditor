<?php

use Galaxia\Director;
use Galaxia\Flash;
use Galaxia\Sql;
use GalaxiaEditor\input\Input;


$editor->view = 'item/new/new';


// item validation

foreach ($_POST['item'] ?? [] as $name => $value) {
    if (!isset($item['inputs'][$name])) continue;

    $input = Input::validateInput($item['inputs'][$name], $value);

    if ($input['dbUnique']) {
        $query = Sql::selectOne($item['gcInsert']);
        $query .= Sql::selectWhere([$item['gcTable'] => [$input['nameFromDb'] => '=']]);
        $query .= Sql::selectLimit(0, 1);

        $stmt = $db->prepare($query);
        $stmt->bind_param('s', $input['value']);
        $stmt->bind_result($rowId);
        $stmt->execute();
        $stmt->fetch();
        $stmt->close();

        if ($rowId)
            $input['errors'][] = 'Must be unique. An item with that value already exists.';
    }

    foreach ($input['errors'] as $msg) {
        Flash::error($msg, 'form', $input['name']);
        if ($input['lang']) {
            $langSelectClass[$input['lang']] = 'btn-red';
        }
    }

    if ($input['value'] !== $input['valueFromDb'])
        $itemChanges[$input['nameFromDb']] = $input['valueToDb'];

    $item['inputs'][$name] = $input;
}

if (Flash::hasError()) return;
if (!$itemChanges) {
    Flash::warning('Item not added.');
    if (isset($_POST['submitAndGoBack'])) Director::redirect('edit/' . $pgSlug);
    return;
}




// item insert

$values = array_values($itemChanges);
try {
    $query = Sql::queryInsert($item['gcInsert'], $itemChanges);

    $stmt = $db->prepare($query);
    $types = str_repeat('s', count($values));
    $stmt->bind_param($types, ...$values);
    $success = $stmt->execute();
    $itemIdNew = $stmt->insert_id;
    $stmt->close();

    if (!$success) {
        Flash::error('new-post - item not inserted.');
        return;
    }

} catch (Exception $e) {
    Flash::error('new-post - Unable to insert item.');
    Flash::error($e->getMessage());
    return;
}




//  history

foreach ($itemChanges as $key => $value)
    insertHistory($uniqueId, $item['gcTable'], $itemIdNew, $key, '', 3, $value, $me->id);




// finish

$app->cacheDelete('editor');
Flash::info(sprintf(t('Added: %s.'), t($geConf[$pgSlug]['gcTitleSingle'])));

if (!in_array($pgSlug, ['users', 'passwords'])) {
    $app->cacheDelete(['app', 'fastroute']);
    $app->generateSitemap($db);
    if (file_exists($app->dir .'src/script/_editor-item-update-hard.php'))
        include $app->dir .'src/script/_editor-item-update-hard.php';
}

if (isset($_POST['submitAndGoBack'])) Director::redirect('edit/' . $pgSlug);
if (isset($_POST['submitAndAddMore'])) Director::redirect('edit/' . $pgSlug . '/new');
Director::redirect('edit/' . $pgSlug . '/' . $itemIdNew);
