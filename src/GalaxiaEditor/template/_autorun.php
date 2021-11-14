<?php


use Galaxia\G;
use Galaxia\Sql;


$pgTitle = $_SERVER['SERVER_NAME'];
$hdTitle = $_SERVER['SERVER_NAME'];


// return if login page

if (substr($editor->logic, 0, 6) == 'login/') return;




// variables

$includeTrix        = false;
$showSwitchesLang   = false;
$passwordColsFound  = false;
$chatInclude        = isset(G::$conf['chat']);
$chatIncludeCurrent =
    !in_array($pgSlug, ['users', 'passwords', 'history']) &&
    ((isset($itemId) || isset($imgSlug)) || $pgSlug == 'chat');
$itemChanges        = [];




// load all pages

$pageById = [];

$query = Sql::select(['page' => ['pageId', 'pageStatus', 'pageSlug_', 'pageTitle_']], G::langs());

$stmt = G::prepare($query);
$stmt->execute();
$result = $stmt->get_result();
while ($data = $result->fetch_assoc()) {
    $pageById[$data['pageId']]['pageStatus'] = $data['pageStatus'];
    foreach (G::langs() as $lang) {
        $pageById[$data['pageId']]['slug'][$lang]  = $data['pageSlug_' . $lang];
        $pageById[$data['pageId']]['title'][$lang] = $data['pageTitle_' . $lang];
        $pageById[$data['pageId']]['url'][$lang]   = G::addLangPrefix($data['pageSlug_' . $lang], $lang);
    }
}
$stmt->close();
