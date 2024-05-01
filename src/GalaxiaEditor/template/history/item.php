<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12


// get items from database

use Galaxia\G;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\E;


E::$historyItems = [];

$query = Sql::select(['_geHistory' => ['_geHistoryId', '_geUserId', 'uniqueId', 'inputKey', 'fieldKey', 'action', 'content', 'timestampCreated']]);
$query .= Sql::selectWhere(['_geHistory' => ['tabName' => '=', 'tabId' => '=']]);
$query .= Sql::selectOrderBy(['_geHistory' => ['timestampCreated' => 'DESC']]);

$result = G::execute($query, [E::$tabName, E::$tabId]);

while ($data = $result->fetch_assoc()) {
    if (!isset(E::$historyItems[$data['uniqueId']])) {
        E::$historyItems[$data['uniqueId']]            = [];
        E::$historyItems[$data['uniqueId']]['changes'] = [];
        E::$historyItems[$data['uniqueId']]['action']  = $data['action'];
        E::$historyItems[$data['uniqueId']]['created'] = $data['timestampCreated'];
        E::$historyItems[$data['uniqueId']]['userId']  = $data['_geUserId'];
    }

    E::$historyItems[$data['uniqueId']] ??= [];



    $lang = (substr($data['inputKey'], -3, 1) == '_') ? substr($data['inputKey'], -2) : '';

    $name = $data['inputKey'];
    if (!empty($data['fieldKey'])) {
        $name = $data['fieldKey'];
    } else {
        if (isset(E::$historyInputKeys[E::$tabName])) {
            $name = E::$historyInputKeys[E::$tabName][$data['inputKey']] ?? $data['inputKey'];
        }
    }

    $content = $data['content'];
    if ($data['content'] == '') $content = '[' . Text::t('Empty') . ']';
    if ($data['inputKey'] == 'status')
        if (isset(E::$historyStatusNames[E::$tabName]))
            if (isset(E::$historyStatusNames[E::$tabName][$data['content']]))
                $content = Text::t(E::$historyStatusNames[E::$tabName][$data['content']]);



    E::$historyItems[$data['uniqueId']]['changes'] ??= [];

    E::$historyItems[$data['uniqueId']]['changes'][] = [
        'name'     => $name,
        'content'  => $content,
        'lang'     => $lang,
        'inputKey' => $data['inputKey'],
        'fieldKey' => $data['fieldKey'],
    ];
}
$itemCount = count(E::$historyItems);




E::$hdTitle = sprintf(Text::t('History of %s'), (E::$historyPageNames[E::$tabName] ?? E::$tabName) . ': ' . E::$tabId);
E::$pgTitle = sprintf(Text::t('History of %s'), (E::$historyPageNames[E::$tabName] ?? E::$tabName) . ': ' . E::$tabId);
