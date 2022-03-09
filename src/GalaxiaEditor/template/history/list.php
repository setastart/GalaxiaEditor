<?php

namespace GalaxiaEditor\history;

use Galaxia\G;
use Galaxia\Pagination;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\Cache;
use GalaxiaEditor\E;


// ajax

if (G::$req->xhr) {
    G::$editor->layout = 'none';
    G::$editor->view   = 'history/results';
}


$items = Cache::historyItems(function() {
    $query = Sql::select(['_geHistory' => ['_geHistoryId', '_geUserId', 'uniqueId', 'tabName', 'tabId', 'inputKey', 'fieldKey', 'action', 'content', 'timestampCreated']]);
    $query .= Sql::selectOrderBy(['_geHistory' => ['uniqueId' => 'DESC']]);

    $stmt = G::prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($data = $result->fetch_assoc()) {
        if (!isset($items[$data['uniqueId']])) {
            $items[$data['uniqueId']]['user']     = E::$historyUserNames[$data['_geUserId']] ?? $data['_geUserId'];
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
            if (isset(E::$historyInputKeys[$data['tabName']]))
                $name = E::$historyInputKeys[$data['tabName']][$data['inputKey']] ?? $data['inputKey'];
        }

        $content = $data['content'];
        if ($data['inputKey'] == 'status')
            if (isset(E::$historyStatusNames[$data['tabName']]))
                if (isset(E::$historyStatusNames[$data['tabName']][$data['content']]))
                    $content = Text::t(E::$historyStatusNames[$data['tabName']][$data['content']]);

        $name                                  = trim($name);
        $items[$data['uniqueId']]['changes'][] = [
            'name'    => Text::t($name),
            'content' => $content,
            'lang'    => $lang,
        ];
    }
    $stmt->close();

    return $items ?? [];
});




// make html for all rows, using cache

E::$historyRows = Cache::historyRows(function() use ($items): array {
    foreach ($items as $itemId => $item) {
// @formatter:off
$ht = '<a class="row ' . $item['action'] . '" href="/edit/' . Text::h(E::$pgSlug) . '/' . Text::h($item['tabName']) . '/' . Text::h($item['tabId']) . '#' . Text::h($itemId) . '">' . PHP_EOL;
$ht .= '    <div class="col flex1">' . PHP_EOL;
$ht .= '        <small class="grey">table: </small>' . Text::h(E::$historyPageNames[$item['tabName']] ?? $item['tabName']) . '<br>' . PHP_EOL;
$ht .= '        <small class="grey">Id: </small>' . Text::h($item['tabId']) . '<br>' . PHP_EOL;
$ht .= '        <small class="grey">User: </small>' . Text::h($item['user']) . '<br>' . PHP_EOL;
$ht .= '        <small class="">' . Text::h(Text::formatDate($item['created'], 'd MMM y')) . '</small><br>' . PHP_EOL;
$ht .= '        <small class="">' . date('G:i', $item['created']) . '</small><br>' . PHP_EOL;
$ht .= '    </div>' . PHP_EOL;
$ht .= '    <div class="col flex2">' . PHP_EOL;
        foreach ($item['changes'] as $change) {
$ht .= '    <div class="col flex">' . PHP_EOL;
$ht .= '        <div class="col flex2"><span class="input-label-lang">' . Text::h($change['lang']) . '</span> ' . Text::h($change['name']) . ' - ' . Text::h($item['inputKey']) . '</div>' . PHP_EOL;
$ht .= '        <div class="col flex3"><span class="input-label-lang">' . Text::h($change['lang']) . '</span> ' . Text::firstLine($change['content'] ?? '') . '</div>' . PHP_EOL;
$ht .= '    </div>' . PHP_EOL;
        }
$ht .= '    </div>' . PHP_EOL;
$ht .= '</a>' . PHP_EOL;
        $rows[$itemId] = $ht;
// @formatter:on
    }
    return $rows ?? [];
});




// pagination

E::$pagination = new Pagination($_POST['page'] ?? 1, $_POST['itemsPerPage'] ?? 50);
E::$pagination->setItemCounts(count($items), count(E::$historyRows));

E::$historyRows = E::$pagination->sliceRows(E::$historyRows);




// finish

E::$hdTitle = Text::t(E::$section['gcTitlePlural']);
E::$pgTitle = Text::t(E::$section['gcTitlePlural']);
