<?php

$pgTitle = $_SERVER['SERVER_NAME'];
$hdTitle = $_SERVER['SERVER_NAME'];


// return if login page

if (substr($app->logic, 0, 6) == 'login/') return;




// variables

$includeTrix        = false;
$showSwitchesLang   = false;
$passwordColsFound  = false;
$chatInclude        = isset($geConf['chat']);
$chatIncludeCurrent =
    !in_array($pgSlug, ['users', 'passwords', 'history']) &&
    ((isset($itemId) || isset($imgSlug)) || $pgSlug == 'chat');
$itemChanges = [];




// load all pages

$pageById = [];

$query = querySelect(['page' => ['pageId', 'pageStatus', 'pageSlug_', 'pageTitle_']], $app->langs);
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
while ($data = $result->fetch_assoc()) {
    $pageById[$data['pageId']]['pageStatus'] = $data['pageStatus'];
    foreach ($app->langs as $lang) {
        $pageById[$data['pageId']]['slug'][$lang]    = $data['pageSlug_' . $lang];
        $pageById[$data['pageId']]['title'][$lang]   = $data['pageTitle_' . $lang];
        $pageById[$data['pageId']]['url'][$lang]     = $app->addLangPrefix($data['pageSlug_' . $lang], $lang);
    }
}
$stmt->close();
