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


unset(E::$itemChanges['passwordCurrent']);
unset(E::$itemChanges['passwordRepeat']);
if (isset(E::$itemChanges['passwordHash']))
    E::$itemChanges['passwordHash'] = password_hash(E::$itemChanges['passwordHash'], PASSWORD_BCRYPT);


$params = array_values(E::$itemChanges);
$params[] = E::$itemId;
$types = str_repeat('s', count($params));

$query = Sql::update(E::$item['gcUpdate']);
$query .= Sql::updateSet(array_keys(E::$itemChanges));
$query .= Sql::updateWhere([E::$item['gcTable'] => [E::$item['gcTable'] . 'Id']]);

try {
    $stmt = G::prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    if ($affectedRows < 1) {
        Flash::error('item-update - Unable to update database.');
    } else {
        foreach (E::$itemChanges as $inputKey => $content) {
            $lang = E::$item['inputs'][$inputKey]['lang'] ? ' - ' . E::$item['inputs'][$inputKey]['lang'] : '';
            Flash::info(Text::t('Updated') . ': ' . Text::t(E::$item['inputs'][$inputKey]['label']) . $lang);
            Flash::info(Text::t('Updated'), 'form', E::$item['inputs'][$inputKey]['name']);

            if (E::$item['gcTable'] == '_geUser')
                if ($inputKey == 'passwordHash')
                    continue;

            History::insert(E::$uniqueId, E::$item['gcTable'], E::$itemId, $inputKey, '', 2, $content, G::$me->id);
        }
    }
} catch (Exception $e) {
    Flash::error('item-update - Unable to save changes to item.');
    geD($e->getMessage(), $query, $types, $params);
    return;
}
