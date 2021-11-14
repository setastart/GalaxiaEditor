<?php


// variables

use Galaxia\G;
use Galaxia\Flash;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\input\Input;


$uniqueId        = uniqid(true);
$item            = &G::$conf[$pgSlug]['gcItem'];
$langSelectClass = [];
foreach (G::locales() as $lang => $locale) {
    $langSelectClass[$lang] = '';
}
$includeTrix      = true;
$querySelectWhere = [$item['gcTable'] => [$item['gcTable'] . 'Id' => '=']];
$fieldsNew        = [];
$fieldsDel        = [];
$fieldsUpd        = [];




// skip for new item page

if ($itemId == 'new') return;




// restrict edit acces to only own user

if ($item['gcUpdateOnlyOwn'] ?? false) {
    if (!$me->hasPerm('dev') && $me->id != $itemId) {
        Flash::error(Text::t('Redirected. You don\'t have access to that page.'));
        G::redirect('/edit/' . $editor->homeSlug);
    }
}




// item validation

$query = Sql::selectOne($item['gcSelect']);
$query .= Sql::selectWhere($querySelectWhere);
$query .= Sql::selectLimit(0, 1);

$stmt = G::prepare($query);
$stmt->bind_param('d', $itemId);
$stmt->bind_result($itemExists);
$stmt->execute();
$stmt->fetch();
$stmt->close();

if (!$itemExists) {
    Flash::error(sprintf(Text::t('%s with id %s does not exist.'), Text::t(G::$conf[$pgSlug]['gcTitleSingle']), Text::h($itemId)));
    G::redirect('edit/' . $pgSlug);
}




// query item

$query = Sql::select($item['gcSelect']);
$query .= Sql::selectLeftJoinUsing($item['gcSelectLJoin'] ?? []);
$query .= Sql::selectWhere($querySelectWhere);

$stmt = G::prepare($query);
$stmt->bind_param('d', $itemId);
$stmt->execute();
$result = $stmt->get_result();
$data   = $result->fetch_assoc();
$stmt->close();

$item['data'] = array_map(function($value) {
    return ($value === null) ? null : strval($value);
}, $data);




// query extras

$extras = [];
foreach ($item['gcSelectExtra'] as $table => $cols) {
    $query = Sql::select([$table => $cols]);
    $query .= Sql::selectOrderBy([$table => [$cols[1] => 'ASC']]);
    $stmt  = G::prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($extraData = $result->fetch_assoc()) {
        $extraData        = array_map('strval', $extraData);
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

    $input = Input::prepare($input, $extras);

    if ($input['type'] == 'password' || substr($inputKey, 0, 8) == 'password') $passwordColsFound = true;
    if (isset($input['lang']) && count(G::langs()) > 1) $showSwitchesLang = true;

    $inputNew = [
        'label'      => $input['label'] ?? G::$conf[$pgSlug]['gcColNames'][$inputKey] ?? $inputKey,
        'name'       => 'item[' . $inputKey . ']',
        'nameFromDb' => $inputKey,
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
    $item['gcInfo'][$inputKey]['label'] = G::$conf[$pgSlug]['gcColNames'][$inputKey] ?? $inputKey;

    $value = $item['data'][$inputKey];
    if ($input['type'] == 'timestamp')
        if (!empty($value)) $value = Text::formatDate($item['data'][$inputKey], 'd MMM y - HH:mm');
    $item['gcInfo'][$inputKey]['value'] = $value;
}


$titleTemp = 'Item';
if (is_array($item['gcColKey'])) {
    foreach ($item['gcColKey'] as $i => $val) {
        if (empty($item['data'][$val] ?? '')) continue;
        $item['gcColKey'] = $item['gcColKey'][$i];
        break;
    }
    if (is_array($item['gcColKey'])) $item['gcColKey'] = $item['gcColKey'][array_key_first($item['gcColKey'])];
}

$titleTemp = $item['data'][$item['gcColKey']];
if (empty($titleTemp)) $titleTemp = $itemId;
if (substr($item['gcColKey'], 0, 9) == 'timestamp') $titleTemp = Text::formatDate($titleTemp, 'd MMM y - HH:mm');
$pgTitle = Text::t(G::$conf[$pgSlug]['gcTitleSingle']) . ': ' . $titleTemp;
$hdTitle = Text::t('Editing') . ': ' . $pgTitle;




// add redirect module

if ($item['gcRedirect']) {
    $table = G::$conf[$pgSlug]['gcItem']['gcTable'];

    G::$conf[$pgSlug]['gcItem']['gcModules'][] = [
        'gcTable'               => $table . 'Redirect',
        'gcModuleType'          => 'fields',
        'gcModuleTitle'         => '',
        'gcModuleShowUnused'    => ['gcPerms' => ['dev']],
        'gcModuleDeleteIfEmpty' => [$table . 'RedirectSlug'],
        'gcModuleMultiple'      => ['Redirect' => ['reorder' => false, 'unique' => [$table . 'RedirectSlug'], 'label' => 'Redirects']],

        'gcSelect'        => [$table . 'Redirect' => [$table . 'RedirectId', 'fieldKey', $table . 'RedirectSlug', 'position']],
        'gcSelectLJoin'   => [],
        'gcSelectOrderBy' => [],
        'gcSelectExtra'   => [],
        'gcUpdate'        => [$table . 'Redirect' => [$table . 'RedirectSlug']],

        'gcInputs' => [$table . 'RedirectSlug' => ['label' => 'Slug', 'type' => 'slug', 'dbUnique' => true]],

        'gcInputsWhereCol' => [
            'Redirect' => [$table . 'RedirectSlug' => ['type' => 'text']],
        ],

        'gcInputsWhereParent' => [],
    ];
}


// query modules

$modules = &G::$conf[$pgSlug]['gcItem']['gcModules'];

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
