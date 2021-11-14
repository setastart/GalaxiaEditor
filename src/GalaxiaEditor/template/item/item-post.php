<?php

use Galaxia\G;
use Galaxia\Flash;
use Galaxia\Sql;
use Galaxia\Text;


$editor->view = 'item/item';




// item validation

include $editor->dirView . 'item/item-validation.php';




// module validation

foreach ($_POST['modules'] ?? [] as $moduleKey => $postModule) {
    if (!isset($modules[$moduleKey])) continue;

    if ($modules[$moduleKey]['gcModuleType'] == 'fields') {
        include $editor->dirView . 'item/modules/fields-validation.php';
    }
}




// finish validation

if (Flash::hasError()) return;
if (!$itemChanges && !$fieldsNew && !$fieldsDel && !$fieldsUpd) {
    Flash::warning(Text::t('No changes were made.'));
    if (isset($_POST['submitAndGoBack'])) G::redirect('edit/' . $pgSlug);
    return;
}




// update item

if ($itemChanges)
    include $editor->dirView . 'item/item-update.php';




// update modules

if ($fieldsNew || $fieldsDel || $fieldsUpd)
    include $editor->dirView . 'item/modules/fields-update.php';




// update timstampModified

$params = [date('Y-m-d G:i:s'), $itemId];
$query = Sql::update($item['gcUpdate']);
$query .= Sql::updateSet(['timestampModified']);
$query .= Sql::updateWhere([$item['gcTable'] => [$item['gcTable'] . 'Id']]);
$stmt = G::prepare($query);
$stmt->bind_param('sd', ...$params);
$stmt->execute();
$affectedRows = $stmt->affected_rows;
$stmt->close();




// finish

if (!in_array($pgSlug, ['users', 'passwords'])) {
    $slugs = [$item['gcTable'] . 'Slug'];
    foreach (G::langs() as $lang) $slugs[] = $item['gcTable'] . 'Slug_' . $lang;

    if ($item['gcRedirect']) {
        $redirectTable     = $item["gcTable"] . 'Redirect';
        $redirectTableId   = $item["gcTable"] . 'Id';
        $redirectTableSlug = $redirectTable . 'Slug';
        $redirectFieldKey  = 'Redirect';

        foreach ($slugs as $slug) {
            if (!isset($itemChanges[$slug])) continue;

            $oldSlug = $item['inputs'][$slug]['valueFromDb'] ?? '';
            $newSlug = $itemChanges[$slug] ?? '';
            if (!$oldSlug || !$newSlug) continue;

            try {
                $values = [null, '', $oldSlug, $newSlug];
                $query = Sql::deleteIn($redirectTable, [], $redirectTableSlug, $values);

                $stmt = G::prepare($query);
                $stmt->bind_param('ssss', ...$values);
                $stmt->execute();
                $affectedRows = $stmt->affected_rows;
                $stmt->close();

                Flash::devlog('item-post - deleted ' . $affectedRows . ' old redirect slug: ' . Text::h($oldSlug));
            } catch (Exception $e) {
                Flash::devlog('item-post - could not delete old redirect slug: ' . Text::h($oldSlug));
            }

            try {
                $query = Sql::queryInsert(
                    [$redirectTable => [$redirectTableSlug]],
                    [$redirectTableId => $itemId, $redirectTableSlug => $oldSlug, 'fieldKey' => $redirectFieldKey]
                );
                $stmt = G::prepare($query);
                $stmt->bind_param('dss', $itemId, $oldSlug, $redirectFieldKey);
                $success = $stmt->execute();
                $itemIdNew = $stmt->insert_id;
                $stmt->close();
                if ($success) Flash::info('item-post - added redirect slug: ' . Text::h($oldSlug));
            } catch (Exception $e) {
                Flash::devlog('item-post - Unable to insert redirect slug: ' . Text::h($oldSlug));
                Flash::devlog($e->getMessage());
                return;
            }

        }
    }


    if ($itemChanges || $fieldsNew || $fieldsDel || $fieldsUpd) {
        G::cacheDelete(['app', 'fastroute']);
        G::cacheDelete('editor');

        if (
            isset($itemChanges[$item['gcTable'] . 'Status']) ||
            count(array_intersect(array_keys($itemChanges), $slugs)) > 0
        ) {
            G::routeSitemap();
            if (file_exists(G::dir() .'src/script/_editor-item-update-hard.php')) {
                include G::dir() .'src/script/_editor-item-update-hard.php';
            }
        } else {
            if (file_exists(G::dir() .'src/script/_editor-item-update-soft.php')) {
                include G::dir() .'src/script/_editor-item-update-soft.php';
            }
        }

    }

}

if (isset($_POST['submitAndGoBack'])) G::redirect('edit/' . $pgSlug);
G::redirect('edit/' . $pgSlug . '/' . $itemId);
