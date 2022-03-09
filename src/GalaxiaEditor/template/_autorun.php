<?php


use Galaxia\G;
use Galaxia\Sql;
use GalaxiaEditor\E;


E::$pgTitle = G::$req->host;
E::$hdTitle = G::$req->host;


// return if login page

if (str_starts_with(G::$editor->logic, 'login/')) return;




// variables

E::$chatInclude        = isset(E::$conf['chat']);
// todo: check gcPageType of section instead of hardcoding
E::$chatIncludeCurrent = !in_array(E::$pgSlug, ['users', 'passwords', 'history']) && (E::$itemId || E::$imgSlug || E::$pgSlug == 'chat');




// load all pages

$query = Sql::select(['page' => ['pageId', 'pageStatus', 'pageSlug_', 'pageTitle_']], G::langs());

$stmt = G::prepare($query);
$stmt->execute();
$result = $stmt->get_result();
while ($data = $result->fetch_assoc()) {
    E::$pageById[$data['pageId']]['pageStatus'] = $data['pageStatus'];
    foreach (G::langs() as $lang) {
        E::$pageById[$data['pageId']]['slug'][$lang]  = $data['pageSlug_' . $lang];
        E::$pageById[$data['pageId']]['title'][$lang] = $data['pageTitle_' . $lang];
        E::$pageById[$data['pageId']]['url'][$lang]   = G::addLangPrefix($data['pageSlug_' . $lang], $lang);
    }
}
$stmt->close();
