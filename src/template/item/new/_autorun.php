<?php


$pgTitle = t('+ Add') . ' ' . t($geConf[$pgSlug]['gcTitleSingle']);
$hdTitle = t('+ Add') . ' ' . t($geConf[$pgSlug]['gcTitleSingle']);


// query extras

$extras = [];
foreach ($item['gcSelectExtra'] as $table => $cols) {
    $query = querySelect([$table => $cols]);
    $query .= querySelectOrderBy([$table => [$cols[1] => 'ASC']]);

    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($extraData = $result->fetch_assoc()) {
        $extraData = array_map('strval', $extraData);
        $extras[$table][] = $extraData;
    }
    $stmt->close();
}




foreach ($item['gcInputs'] as $inputName => $input) {
    if ($input['type'] == 'select' && isset($input['geExtraOptions'])) {
        foreach ($input['geExtraOptions'] as $key => $val) {
            foreach ($val as $colKey => $colVal) {
                if (!isset($extras[$key])) continue;
                foreach ($extras[$key] as $option) {
                    $input['options'][$option[$colKey]] = ['label' => $option[$colVal]];
                }
            }
        }
    }

    $item['inputs'][$inputName] = array_merge($input, [
        'label'       => $geConf[$pgSlug]['gcColNames'][$inputName] ?? $inputName,
        'name'        => 'item[' . $inputName . ']',
        'nameFromDb'  => $inputName,
    ]);
    if ($input['type'] == 'timestamp') $item['inputs'][$inputName]['value'] = date('Y-m-d 00:00');
    if ($input['type'] == 'datetime')  $item['inputs'][$inputName]['value'] = date('Y-m-d 00:00');

    if (isset($input['lang'])) $showSwitchesLang = true;
}
