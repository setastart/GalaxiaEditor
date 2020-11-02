<?php


// get items from database

use Galaxia\Sql;


$items = [];

$query = Sql::select(['_geHistory' => ['_geHistoryId', 'uniqueId', 'inputKey', 'fieldKey', 'action', 'content', 'timestampCreated']]);
$query .= Sql::selectWhere(['_geHistory' => ['tabName' => '=', 'tabId' => '=']]);
$query .= Sql::selectOrderBy(['_geHistory' => ['timestampCreated' => 'DESC']]);

$stmt = $db->prepare($query);
$stmt->bind_param('ss', $tabName, $tabId);
$stmt->execute();
$result = $stmt->get_result();

while ($data = $result->fetch_assoc()) {
    if (!isset($items[$data['uniqueId']])) {
        $items[$data['uniqueId']] = [];
        $items[$data['uniqueId']]['changes'] = [];
        $items[$data['uniqueId']]['action'] = $data['action'];
        $items[$data['uniqueId']]['created'] = $data['timestampCreated'];
    }

    $items[$data['uniqueId']] = $items[$data['uniqueId']] ?? [];



    $lang = (substr($data['inputKey'], -3, 1) == '_') ? substr($data['inputKey'], -2) : '';

    $name = $data['inputKey'];
    if (!empty($data['fieldKey'])) {
        $name = $data['fieldKey'];
    } else {
        if (isset($inputKeys[$tabName])) {
            $name = $inputKeys[$tabName][$data['inputKey']] ?? $data['inputKey'];
        }
    }

    $content = $data['content'];
    if ($data['content'] == '') $content = '[' . t('Empty') . ']';
    if ($data['inputKey'] == 'status')
        if (isset($statusNames[$tabName]))
            if (isset($statusNames[$tabName][$data['content']]))
                $content = t($statusNames[$tabName][$data['content']]);



    $items[$data['uniqueId']]['changes'] = $items[$data['uniqueId']]['changes'] ?? [];
    $items[$data['uniqueId']]['changes'][] = [
        'name' => $name,
        'content' => $content,
        'lang'    => $lang,
    ];
}
$stmt->close();
$itemCount = count($items);




$hdTitle = sprintf(t('History of %s'), ($pageNames[$tabName] ?? $tabName) . ': ' . $tabId);
$pgTitle = sprintf(t('History of %s'), ($pageNames[$tabName] ?? $tabName) . ': ' . $tabId);
