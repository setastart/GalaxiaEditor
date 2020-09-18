<?php

use Galaxia\{Director, Pagination};


// ajax

if (Director::$ajax) {
    $editor->layout = 'none';
    $editor->view = 'list/results';
}




$items = $app->cacheGet('editor', 2, 'list', $pgSlug, 'items', function() use ($db, $userNames) {
    $query = querySelect(['_geHistory' => ['_geHistoryId', '_geUserId', 'uniqueId', 'tabName', 'tabId', 'inputKey', 'fieldKey', 'action', 'content', 'timestampCreated']]);
    $query .= querySelectOrderBy(['_geHistory' => ['uniqueId' => 'DESC']]);

    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($data = $result->fetch_assoc()) {
        if (!isset($items[$data['uniqueId']])) {
            $items[$data['uniqueId']]['user']     = $userNames[$data['_geUserId']] ?? $data['_geUserId'];
            $items[$data['uniqueId']]['tabName']  = $data['tabName'];
            $items[$data['uniqueId']]['tabId']    = $data['tabId'];
            $items[$data['uniqueId']]['inputKey'] = $data['inputKey'];
            $items[$data['uniqueId']]['fieldKey'] = $data['fieldKey'];
            $items[$data['uniqueId']]['action']   = $data['action'];
            $items[$data['uniqueId']]['created']  = $data['timestampCreated'];
            $items[$data['uniqueId']]['changes']  = [];
        }

        $lang = (substr($data['inputKey'], -3, 1) == '_') ? substr($data['inputKey'], -2) : '';

        $name = $data['inputKey'];
        if (!empty($data['fieldKey'])) {
            $name = $data['fieldKey'];
        } else {
            if (isset($inputKeys[$data['tabName']]))
                $name = $inputKeys[$data['tabName']][$data['inputKey']] ?? $data['inputKey'];
        }

        $content = $data['content'];
        if ($data['inputKey'] == 'status')
            if (isset($statusNames[$data['tabName']]))
                if (isset($statusNames[$data['tabName']][$data['content']]))
                    $content = t($statusNames[$data['tabName']][$data['content']]);

        $name = trim($name);
        $items[$data['uniqueId']]['changes'][] = [
            'name'    => t($name),
            'content' => $content,
            'lang'    => $lang,
        ];
    }
    $stmt->close();

    return $items;
});
$rowsTotal = count($items);




// make html for all rows, using cache

$rows = $app->cacheGet('editor', 3, 'list', $pgSlug, 'rows', function() use ($pgSlug, $items, $pageNames) {
    foreach ($items as $itemId => $item) {
$ht = '<a class="row ' . $item['action'] . '" href="/edit/' . h($pgSlug) . '/' . h($item['tabName']) . '/' . h($item['tabId']) . '#' . h($itemId) . '">' . PHP_EOL;
$ht .= '    <div class="col flex1">' . PHP_EOL;
$ht .= '        <small class="grey">table: </small>' . h($pageNames[$item['tabName']] ?? $item['tabName']) . '<br>' . PHP_EOL;
$ht .= '        <small class="grey">Id: </small>' . h($item['tabId']) . '<br>' . PHP_EOL;
$ht .= '        <small class="grey">User: </small>' . h($item['user']) . '<br>' . PHP_EOL;
$ht .= '        <small class="">' . h(gFormatDate($item['created'], 'd MMM y')) . '</small><br>' . PHP_EOL;
$ht .= '        <small class="">' . date('G:i', $item['created']) . '</small><br>' . PHP_EOL;
$ht .= '    </div>' . PHP_EOL;
$ht .= '    <div class="col flex2">' . PHP_EOL;
        foreach ($item['changes'] as $change) {
$ht .= '    <div class="col flex">' . PHP_EOL;
$ht .= '        <div class="col flex2"><span class="input-label-lang">' . h($change['lang']) . '</span> ' . h($change['name']) . ' - ' . h($item['inputKey']) . '</div>' . PHP_EOL;
$ht .= '        <div class="col flex3"><span class="input-label-lang">' . h($change['lang']) . '</span> ' . firstLine($change['content'] ?? '') . '</div>' . PHP_EOL;
$ht .= '    </div>' . PHP_EOL;
        }
$ht .= '    </div>' . PHP_EOL;
$ht .= '</a>' . PHP_EOL;
        $rows[$itemId] = $ht;
    }
    return $rows;
});




// pagination

$pagination = new Pagination((int) ($_POST['page'] ?? 1), (int) ($_POST['itemsPerPage'] ?? 50));
$rowsFiltered = count($rows);
$pagination->setItemsTotal($rowsFiltered);
$offset = $pagination->itemFirst - 1;
$length = $pagination->itemsPerPage;
if ($length >= $pagination->itemsTotal) $length = null;

$rows = array_slice($rows, $offset, $length);




// finish

$hdTitle = t($geConf[$pgSlug]['gcTitlePlural']);
$pgTitle = t($geConf[$pgSlug]['gcTitlePlural']);
