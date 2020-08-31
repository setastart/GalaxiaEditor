<?php


// variables

$uniqueId = uniqid(true);
$item = &$geConf[$pgSlug]['gcItem'];
$langSelectClass = [];
foreach ($app->locales as $lang => $locale) {
    $langSelectClass[$lang] = '';
}
$includeTrix = true;
$querySelectWhere = [$item['gcTable'] => [$item['gcTable'] . 'Id' => '=']];
$fieldsNew   = [];
$fieldsDel   = [];
$fieldsUpd   = [];




// skip for new item page

if ($itemId == 'new') return;




// restrict edit acces to only own user

if ($item['gcUpdateOnlyOwn'] ?? false) {
    if (!in_array('dev', $me->perms) && $me->id != $itemId) {
        error(t('Redirected. You don\'t have access to that page.'));
        redirect('/edit/pages');
    }
}




// item validation

$query = querySelectOne($item['gcSelect']);
$query .= querySelectWhere($querySelectWhere);
$query .= querySelectLimit(0, 1);

$stmt = $db->prepare($query);
$stmt->bind_param('d', $itemId);
$stmt->bind_result($itemExists);
$stmt->execute();
$stmt->fetch();
$stmt->close();

if (!$itemExists) {
    error(sprintf(t('%s with id %s does not exist.'), t($geConf[$pgSlug]['gcTitleSingle']), h($itemId)));
    redirect('edit/' . $pgSlug);
}




// query item

$query = querySelect($item['gcSelect']);
$query .= querySelectLeftJoinUsing($item['gcSelectLJoin'] ?? []);
$query .= querySelectWhere($querySelectWhere);

$stmt = $db->prepare($query);
$stmt->bind_param('d', $itemId);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

$item['data'] = array_map('strvalIfNotNull', $data);




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



foreach ($item['gcInputsWhere'] as $colKey => $col) {
    if (!isset($item['data'][$colKey])) continue;
    foreach ($col as $colVal => $inputs) {
        if ($item['data'][$colKey] != $colVal) continue;
        foreach ($inputs as $inputKey => $input) {
            if (!isset($item['gcInputs'][$inputKey])) continue;
            $item['gcInputs'][$inputKey] = array_merge($item['gcInputs'][$inputKey], $input);
        }
    }
}


$firstStatus = '';
foreach ($item['gcInputs'] as $inputKey => $input) {
    if (empty($input)) continue;
    if ($input['type'] == 'status' && !isset($input['options'][$item['data'][$inputKey]])) continue;

    $input = prepareInput($input, $extras);

    if ($input['type'] == 'password' || substr($inputKey, 0, 8) == 'password') $passwordColsFound = true;
    if (isset($input['lang']) && count($app->langs) > 1) $showSwitchesLang = true;

    $inputNew = [
        'label'       => $input['label'] ?? $geConf[$pgSlug]['gcColNames'][$inputKey] ?? $inputKey,
        'name'        => 'item[' . $inputKey . ']',
        'nameFromDb'  => $inputKey,
    ];

    if (!$firstStatus && $input['type'] == 'status') $firstStatus = $inputKey;

    if (array_key_exists($inputKey, $item['data'])) {
        $value = $item['data'][$inputKey];
        if ($input['type'] == 'timestamp') $value = date('Y-m-d H:i:s', $item['data'][$inputKey]);
        if (substr($inputKey, 0, 8) == 'password') $value = '';
        $inputNew['value']       = $value;
        $inputNew['valueFromDb'] = $value;
    }

    $item['inputs'][$inputKey] = array_merge($input, $inputNew);
}




foreach ($item['gcInfo'] as $inputKey => $input) {
    $item['gcInfo'][$inputKey]['label'] = $geConf[$pgSlug]['gcColNames'][$inputKey] ?? $inputKey;

    $value = $item['data'][$inputKey];
    if ($input['type'] == 'timestamp')
        if (!empty($value)) $value = gFormatDate($item['data'][$inputKey], 'd MMM y - HH:mm');
    $item['gcInfo'][$inputKey]['value'] = $value;
}


$titleTemp = 'Item';

if (is_array($item['gcColKey'])) {
    foreach ($item['gcColKey'] as $i => $val) {
        if (empty($item['data'][$val] ?? '')) continue;
        $item['gcColKey'] = $item['gcColKey'][$i];
        break;
    }
}

$titleTemp = $item['data'][$item['gcColKey']];
if (substr($item['gcColKey'], 0, 9) == 'timestamp') $titleTemp = gFormatDate($titleTemp, 'd MMM y - HH:mm');
$pgTitle = t($geConf[$pgSlug]['gcTitleSingle']) . ': ' . $titleTemp;
$hdTitle = t('Editing') . ': ' . $pgTitle;




// add redirect module

if ($item['gcRedirect']) {
    $table = $geConf[$pgSlug]['gcItem']['gcTable'];
    $geConf[$pgSlug]['gcItem']['gcModules'][] = [
        'gcTable'               => $table . 'Redirect',
        'gcModuleType'          => 'fields',
        'gcModuleTitle'         => '',
        'gcModuleShowUnused'    => ['gcPerms' => ['dev']],
        'gcModuleDeleteIfEmpty' => [$table . 'RedirectSlug'],
        'gcModuleMultiple'      => ['Redirect' => ['reorder' => false, 'unique' => [$table . 'RedirectSlug'], 'label' => 'Redirects']],

        'gcSelect' => [$table . 'Redirect' => [$table . 'RedirectId', 'fieldKey', $table . 'RedirectSlug', 'position']],
        'gcSelectLJoin'   => [],
        'gcSelectOrderBy' => [],
        'gcSelectExtra'   => [],
        'gcUpdate' => [$table . 'Redirect' => [$table . 'RedirectSlug']],

        'gcInputs' => [$table . 'RedirectSlug' => ['label' => 'Slug', 'type' => 'slug', 'dbUnique' => true]],
        'gcInputsWhereCol' => [
            'Redirect' => [$table . 'RedirectSlug' => ['type' => 'text']],
        ],
        'gcInputsWhereParent' => [],
    ];
}


// query modules

$modules = &$geConf[$pgSlug]['gcItem']['gcModules'];

foreach ($modules as $moduleKey => &$module) {
    switch ($module['gcModuleType']) {
        case 'fields':
            include $editor->dirView . 'item/modules/fields.php';
            break;
        default:
            geErrorPage(500, 'invalid module');
            break;
    }
}
unset($module);
