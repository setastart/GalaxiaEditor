<?php

$editor->view = 'item/new/new';


// item validation

foreach ($_POST['item'] ?? [] as $name => $value) {
    if (!isset($item['inputs'][$name])) continue;

    $input = validateInput($item['inputs'][$name], $value);

    if ($input['dbUnique']) {
        $query = querySelectOne($item['gcInsert']);
        $query .= querySelectWhere([$item['gcTable'] => [$input['nameFromDb'] => '=']]);
        $query .= querySelectLimit(0, 1);

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
        error($msg, 'form', $input['name']);
        if ($input['lang']) {
            $langSelectClass[$input['lang']] = 'btn-red';
        }
    }

    if ($input['value'] !== $input['valueFromDb'])
        $itemChanges[$input['nameFromDb']] = $input['valueToDb'];

    $item['inputs'][$name] = $input;
}

if (hasError()) return;
if (!$itemChanges) {
    warning('Item not added.');
    if (isset($_POST['submitAndGoBack'])) redirect('edit/' . $pgSlug);
    return;
}




// item insert

$values = array_values($itemChanges);
try {
    $query = queryInsert($item['gcInsert'], $itemChanges);

    $stmt = $db->prepare($query);
    $types = str_repeat('s', count($values));
    $stmt->bind_param($types, ...$values);
    $success = $stmt->execute();
    $itemIdNew = $stmt->insert_id;
    $stmt->close();

    if (!$success) {
        error('new-post - item not inserted.');
        return;
    }

} catch (Exception $e) {
    error('new-post - Unable to insert item.');
    error($e->getMessage());
    return;
}




//  history

foreach ($itemChanges as $key => $value)
    insertHistory($uniqueId, $item['gcTable'], $itemIdNew, $key, '', 3, $value, $me->id);




// finish

$app->cacheDelete('editor', 'list', $pgSlug);
info(sprintf(t('Added: %s.'), t($geConf[$pgSlug]['gcTitleSingle'])));

if (!in_array($pgSlug, ['users', 'passwords'])) {
    $app->cacheDelete(['app', 'fastroute']);
    $app->generateSitemap($db);
    if (file_exists($app->dir .'scripts/_runOnUpdate.php'))
        include $app->dir .'scripts/_runOnUpdate.php';
}

if (isset($_POST['submitAndGoBack'])) redirect('edit/' . $pgSlug);
if (isset($_POST['submitAndAddMore'])) redirect('edit/' . $pgSlug . '/new');
redirect('edit/' . $pgSlug . '/' . $itemIdNew);
