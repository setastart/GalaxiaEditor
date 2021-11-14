<?php


use Galaxia\G;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\input\Input;


$pgTitle = Text::t('+ Add') . ' ' . Text::t(G::$conf[$pgSlug]['gcTitleSingle']);
$hdTitle = Text::t('+ Add') . ' ' . Text::t(G::$conf[$pgSlug]['gcTitleSingle']);


// query extras

$extras = [];
foreach ($item['gcSelectExtra'] as $table => $cols) {
    $query = Sql::select([$table => $cols]);
    $query .= Sql::selectOrderBy([$table => [$cols[1] => 'ASC']]);

    $stmt = G::prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($extraData = $result->fetch_assoc()) {
        $extraData = array_map('strval', $extraData);
        $extras[$table][] = $extraData;
    }
    $stmt->close();
}




foreach ($item['gcInputs'] as $inputKey => $input) {
    $input = Input::prepare($input, $extras);

    $item['inputs'][$inputKey] = array_merge($input, [
        'label'       => $input['label'] ?? G::$conf[$pgSlug]['gcColNames'][$inputKey] ?? $inputKey,
        'name'        => 'item[' . $inputKey . ']',
        'nameFromDb'  => $inputKey,
    ]);
    if ($input['type'] == 'timestamp') $item['inputs'][$inputKey]['value'] = date('Y-m-d 00:00');
    if ($input['type'] == 'datetime')  $item['inputs'][$inputKey]['value'] = date('Y-m-d 00:00');

    if (isset($input['lang'])) $showSwitchesLang = true;
}
