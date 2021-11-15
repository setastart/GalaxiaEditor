<?php


// get items from database

use Galaxia\G;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\E;


$items = [];

$query = Sql::select(['_geHistory' => ['_geHistoryId', 'uniqueId', 'inputKey', 'fieldKey', 'action', 'content', 'timestampCreated']]);
$query .= Sql::selectWhere(['_geHistory' => ['tabName' => '=', 'tabId' => '=']]);
$query .= Sql::selectOrderBy(['_geHistory' => ['timestampCreated' => 'DESC']]);

$stmt = G::prepare($query);
$stmt->bind_param('ss', E::$tabName, E::$tabId);
$stmt->execute();
$result = $stmt->get_result();

while ($data = $result->fetch_assoc()) {
    if (!isset($items[$data['uniqueId']])) {
        $items[$data['uniqueId']]            = [];
        $items[$data['uniqueId']]['changes'] = [];
        $items[$data['uniqueId']]['action']  = $data['action'];
        $items[$data['uniqueId']]['created'] = $data['timestampCreated'];
    }

    $items[$data['uniqueId']] ??= [];



    $lang = (substr($data['inputKey'], -3, 1) == '_') ? substr($data['inputKey'], -2) : '';

    $name = $data['inputKey'];
    if (!empty($data['fieldKey'])) {
        $name = $data['fieldKey'];
    } else {
        if (isset($inputKeys[E::$tabName])) {
            $name = $inputKeys[E::$tabName][$data['inputKey']] ?? $data['inputKey'];
        }
    }

    $content = $data['content'];
    if ($data['content'] == '') $content = '[' . Text::t('Empty') . ']';
    if ($data['inputKey'] == 'status')
        if (isset($statusNames[E::$tabName]))
            if (isset($statusNames[E::$tabName][$data['content']]))
                $content = Text::t($statusNames[E::$tabName][$data['content']]);



    $items[$data['uniqueId']]['changes'] ??= [];

    $items[$data['uniqueId']]['changes'][] = [
        'name'    => $name,
        'content' => $content,
        'lang'    => $lang,
    ];
}
$stmt->close();
$itemCount = count($items);




E::$hdTitle = sprintf(Text::t('History of %s'), ($pageNames[E::$tabName] ?? E::$tabName) . ': ' . E::$tabId);
E::$pgTitle = sprintf(Text::t('History of %s'), ($pageNames[E::$tabName] ?? E::$tabName) . ': ' . E::$tabId);
