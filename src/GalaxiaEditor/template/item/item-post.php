<?php
// Copyright 2017-2023 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\E;


G::$editor->view = 'item/item';




// item validation

include G::$editor->dirView . 'item/item-validation.php';




// module validation

foreach ($_POST['modules'] ?? [] as E::$itemPostModuleKey => E::$itemPostModule) {
    if (!isset(E::$modules[E::$itemPostModuleKey])) continue;

    if (E::$modules[E::$itemPostModuleKey]['gcModuleType'] == 'fields') {
        include G::$editor->dirView . 'item/modules/fields-validation.php';
    }
}




// finish validation

if (Flash::hasError()) return;
if (!E::$itemChanges && !E::$fieldsNew && !E::$fieldsDel && !E::$fieldsUpd) {
    Flash::warning(Text::t('No changes were made.'));
    if (isset($_POST['submitAndGoBack'])) G::redirect('edit/' . E::$pgSlug);
    return;
}




// update item

if (E::$itemChanges)
    include G::$editor->dirView . 'item/item-update.php';




// update modules

if (E::$fieldsNew || E::$fieldsDel || E::$fieldsUpd)
    include G::$editor->dirView . 'item/modules/fields-update.php';




// update timstampModified

$params = [date('Y-m-d G:i:s'), E::$itemId];
$query = Sql::update(E::$item['gcUpdate']);
$query .= Sql::updateSet(['timestampModified']);
$query .= Sql::updateWhere([E::$item['gcTable'] => [E::$item['gcTable'] . 'Id']]);
$stmt = G::prepare($query);
$stmt->bind_param('sd', ...$params);
$stmt->execute();
$affectedRows = $stmt->affected_rows;
$stmt->close();




// finish

if (!in_array(E::$pgSlug, ['users', 'passwords'])) {
    $slugs = [E::$item['gcTable'] . 'Slug'];
    foreach (G::langs() as $lang) $slugs[] = E::$item['gcTable'] . 'Slug_' . $lang;

    if (E::$item['gcRedirect']) {
        $redirectTable     = E::$item["gcTable"] . 'Redirect';
        $redirectTableId   = E::$item["gcTable"] . 'Id';
        $redirectTableSlug = $redirectTable . 'Slug';
        $redirectFieldKey  = 'Redirect';

        foreach ($slugs as $slug) {
            if (!isset(E::$itemChanges[$slug])) continue;

            $oldSlug = E::$item['inputs'][$slug]['valueFromDb'] ?? '';
            $newSlug = E::$itemChanges[$slug] ?? '';
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
                    [$redirectTableId => E::$itemId, $redirectTableSlug => $oldSlug, 'fieldKey' => $redirectFieldKey]
                );
                $stmt = G::prepare($query);
                $stmt->bind_param('dss', E::$itemId, $oldSlug, $redirectFieldKey);
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


    if (E::$itemChanges || E::$fieldsNew || E::$fieldsDel || E::$fieldsUpd) {
        G::cacheDelete(['app', 'fastroute']);
        G::cacheDelete('editor');

        if (
            isset(E::$itemChanges[E::$item['gcTable'] . 'Status']) ||
            count(array_intersect(array_keys(E::$itemChanges), $slugs)) > 0
        ) {
            G::routeSitemap(G::$req->schemeHost());
            if (file_exists(G::dir() .'script/_editor-item-update-hard.php')) {
                include G::dir() .'script/_editor-item-update-hard.php';
            }
        } else {
            if (file_exists(G::dir() .'script/_editor-item-update-soft.php')) {
                include G::dir() .'script/_editor-item-update-soft.php';
            }
        }

    }

}

if (isset($_POST['submitAndGoBack'])) G::redirect('edit/' . E::$pgSlug);
G::redirect('edit/' . E::$pgSlug . '/' . E::$itemId);
