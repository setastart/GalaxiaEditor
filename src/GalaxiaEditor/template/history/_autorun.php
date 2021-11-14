<?php


use Galaxia\G;
use Galaxia\Sql;


$pageNames   = [];
$inputKeys   = [];
$rootSlugs   = [];
$statusNames = [];
foreach (G::$conf as $rootSlug => $confPage) {
    if (!isset($confPage['gcItem'])) continue;
    if (!isset($confPage['gcItem']['gcTable'])) continue;
    $pageNames[$confPage['gcItem']['gcTable']] ??= $confPage['gcMenuTitle'];
    $inputKeys[$confPage['gcItem']['gcTable']] ??= $confPage['gcColNames'];
    $rootSlugs[$confPage['gcItem']['gcTable']] ??= $rootSlug;

    if (!isset($confPage['gcItem']['gcInputs'])) continue;
    if (!isset($confPage['gcItem']['gcInputs']['status'])) continue;
    if (!isset($confPage['gcItem']['gcInputs']['status']['options'])) continue;
    foreach ($confPage['gcItem']['gcInputs']['status']['options'] as $optionId => $option) {
        $statusNames[$confPage['gcItem']['gcTable']][$optionId] = $option['label'];
    }
}




// get user names
$userNames = [];
$query = Sql::select(['_geUser' => ['_geUserId', 'name']]);

$stmt = G::prepare($query);
$stmt->execute();
$result = $stmt->get_result();

while ($data = $result->fetch_assoc()) {
    $userNames[$data['_geUserId']] = $data['name'];
}
$stmt->close();
