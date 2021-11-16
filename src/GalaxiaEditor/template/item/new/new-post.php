<?php

use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\history\History;
use GalaxiaEditor\input\Input;


$editor->view = 'item/new/new';


// item validation

foreach ($_POST['item'] ?? [] as $name => $value) {
    if (!isset($item['inputs'][$name])) continue;

    $input = Input::validate($item['inputs'][$name], $value);

    if ($input['dbUnique']) {
        $query = Sql::selectOne($item['gcInsert']);
        $query .= Sql::selectWhere([$item['gcTable'] => [$input['nameFromDb'] => '=']]);
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
            $langSelectClass[$input['lang']] = 'btn-red';
        }
    }

    if ($input['value'] !== $input['valueFromDb'])
        E::$itemChanges[$input['nameFromDb']] = $input['valueToDb'];

    $item['inputs'][$name] = $input;
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
    $query = Sql::queryInsert($item['gcInsert'], E::$itemChanges);

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
    History::insert($uniqueId, $item['gcTable'], $itemIdNew, $key, '', 3, $value, $me->id);




// finish

G::cacheDelete('editor');
Flash::info(sprintf(Text::t('Added: %s.'), Text::t(E::$section['gcTitleSingle'])));

if (!in_array(E::$pgSlug, ['users', 'passwords'])) {
    G::cacheDelete(['app', 'fastroute']);
    G::routeSitemap(E::$req->schemeHost());
    if (file_exists(G::dir() .'src/script/_editor-item-update-hard.php'))
        include G::dir() .'src/script/_editor-item-update-hard.php';
}

if (isset($_POST['submitAndGoBack'])) G::redirect('edit/' . E::$pgSlug);
if (isset($_POST['submitAndAddMore'])) G::redirect('edit/' . E::$pgSlug . '/new');
G::redirect('edit/' . E::$pgSlug . '/' . $itemIdNew);
