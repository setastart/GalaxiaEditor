<?php

$editor->view = 'image/image';
$mtime = filemtime($app->dirImages . $imgSlug . '/');




// item validation

foreach ($inputs as $name => $input) {
    if (!isset($_POST[$name])) continue;
    $value = $_POST[$name];
    $input = validateInput($input, $value);

    if ($name == 'imgSlug' && $value != $imgSlug) {
        if ($value && is_dir($app->dirImages . $value)) {
            $msg = 'Must be unique. An item with that value already exists.';
            $input['errors'][] = $msg;
            error($msg, 'form', $input['name']);
            if ($input['lang']) $langSelectClass[$input['lang']] = 'btn-red';
        }
    }

    if ($input['value'] !== $input['valueFromDb'])
        $itemChanges[$name] = $input['valueToDb'];

    $inputs[$name] = $input;
}




// finish validation

if (hasError()) return;
if (!$itemChanges) {
    warning(t('No changes were made.'));
    if (isset($_POST['submitAndGoBack'])) redirect('edit/' . $pgSlug);
    return;
}




// update alt and type

$altsAndType = ['alt_', 'type'];
arrayLanguifyRemovePerms($altsAndType, $app->langs);
foreach ($itemChanges as $name => $value) {

    if (!in_array($name, $altsAndType)) continue;

    $file = $app->dirImages . $imgSlug . '/' . $imgSlug . '_' . $name . '.txt';

    if (empty($value)) {
        unlink($file);
        continue;
    }

    $fp = fopen($file, 'w');
    if (fwrite($fp, $value)) {
        info(t('Updated') . ': ' . $name);
        info(t('Updated'), 'form', $name);
    } else {
        info(t('Not updated') . ': ' . $name);
        info(t('Not updated'), 'form', $name);
    }
    fclose($fp);
}




// update mtime

touch($app->dirImages . $imgSlug . '/', $mtime);
foreach ($itemChanges as $name => $value) {
    if ($name != 'timestampM') continue;
    if (touch($app->dirImages . $imgSlug . '/', strtotime($value))) {
        info(t('Updated') . ': ' . $name);
        info(t('Updated'), 'form', $name);
    } else {
        info(t('Not updated') . ': ' . $name);
        info(t('Not updated'), 'form', $name);
    }
}




// rename

if (isset($itemChanges['imgSlug'])) {
    if (gImageSlugRename($app->dirImages, $imgSlug, $itemChanges['imgSlug'])) {
        info(t('Updated') . ': ' . 'Slug');
        info(t('Updated'), 'form', 'imgSlug');

        foreach ($geConf[$pgSlug]['gcImagesInUse'] as $table => $inUse) {
            $imgSlugCol = $inUse['gcSelect'][$table][0];
            $slugChange = [$imgSlugCol => $itemChanges['imgSlug']];
            $params = array_values($slugChange);
            $params[] = $imgSlug;

            $query = queryUpdate([$table => [$imgSlugCol]]);
            $query .= queryUpdateSet(array_keys($slugChange));
            $query .= queryUpdateWhere([$table => [$imgSlugCol]]);

            $affectedRows = 0;
            try {
                $stmt = $db->prepare($query);
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $affectedRows = $stmt->affected_rows;
                $stmt->close();
            } catch (Exception $e) {
                error('image-update - Unable to rename database image slug in: ' . h($table));
                error($e->getMessage());
                continue;
            }
            if ($affectedRows)
                info(h($table) . ': Renamed slugs: ' . h($affectedRows));
        }
        $imgSlug = $itemChanges['imgSlug'];
    } else {
        error('image-update - Unable to update image slug: ' . $imgSlug);
    }
}




// finish

$app->cacheDelete(['app', 'fastroute']);
$app->cacheDelete('editor', 'imageList', $pgSlug);

if (isset($_POST['submitAndGoBack'])) redirect('edit/' . $pgSlug);
redirect('edit/' . $pgSlug . '/' . $imgSlug);
