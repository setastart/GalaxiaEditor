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
use GalaxiaEditor\history\History;
use GalaxiaEditor\input\Input;


G::$editor->view = 'item/new/new';


// item validation

foreach ($_POST['item'] ?? [] as $name => $value) {
    if (!isset(E::$item['inputs'][$name])) continue;

    $input = Input::validate(E::$item['inputs'][$name], $value);

    if ($input['dbUnique']) {
        $query = Sql::selectOne(E::$item['gcInsert']);
        $query .= Sql::selectWhere([E::$item['gcTable'] => [$input['nameFromDb'] => '=']]);
        $query .= Sql::selectLimit(0, 1);

        $stmt = G::prepare($query);
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
            E::$langSelectClass[$input['lang']] = 'btn-red';
        }
    }

    if ($input['value'] !== $input['valueFromDb'])
        E::$itemChanges[$input['nameFromDb']] = $input['valueToDb'];

    E::$item['inputs'][$name] = $input;
}

if (Flash::hasError()) return;
if (!E::$itemChanges) {
    Flash::warning('Item not added.');
    if (isset($_POST['submitAndGoBack'])) G::redirect('edit/' . E::$pgSlug);
    return;
}




// item insert

$values = array_values(E::$itemChanges);
try {
    $query = Sql::queryInsert(E::$item['gcInsert'], E::$itemChanges);

    $stmt = G::prepare($query);
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

foreach (E::$itemChanges as $key => $value)
    History::insert(E::$uniqueId, E::$item['gcTable'], $itemIdNew, $key, '', 3, $value, G::$me->id);




// finish

G::cacheDelete('editor');
Flash::info(sprintf(Text::t('Added: %s.'), Text::t(E::$section['gcTitleSingle'])));

if (!in_array(E::$pgSlug, ['users', 'passwords'])) {
    G::cacheDelete(['app', 'fastroute']);
    G::routeSitemap(G::$req->schemeHost());
    if (file_exists(G::dir() .'script/_editor-item-update-hard.php'))
        include G::dir() .'script/_editor-item-update-hard.php';
}

if (isset($_POST['submitAndGoBack'])) G::redirect('edit/' . E::$pgSlug);
if (isset($_POST['submitAndAddMore'])) G::redirect('edit/' . E::$pgSlug . '/new');
G::redirect('edit/' . E::$pgSlug . '/' . $itemIdNew);
