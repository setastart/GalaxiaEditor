<?php


use Galaxia\App;

function renderForm($action = '', $id = '', $classes = '') {
    if ($action) $action = ' action="' . h($action) . '"';
    if ($id) $id = ' id="' . h($id) . '"';
    if ($classes) $classes = ' class="' . h($classes) . '"';
    $form = '<form' . $action . $id . $classes . ' method="post" enctype="multipart/form-data">' . PHP_EOL;
    $form .= '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '" />' . PHP_EOL;
    echo $form;
}
function renderFormEnd() {
    echo '</form>';
}




function renderStatus($input) {
    $input = array_merge(PROTO_INPUT, $input);

    if (isset($input['gcPerms']))
        foreach ($input['gcPerms'] as $perm)
            $input['cssClass'] .= ' hide-perm-' . $perm;

    $css = 'paper-header-status input-wrap' . $input['cssClass'];
    $css .= empty($input['errors']) ? '' : ' input-wrap-errors';

$ht =  '<div class="' . $css . '">' . PHP_EOL;
$ht .= getRadioInput($input);
$ht .= '    <div class="input-label"><span class="input-changed"></span></div>' . PHP_EOL;

    if (!empty($input['errors'])) {
$ht .= '    <ul class="input-errors">' . PHP_EOL;
        foreach ($input['errors'] as $error) {
$ht .= '        <li class="input-error">' . h($error) . '</div>' . PHP_EOL;
        }
$ht .= '    </ul>' . PHP_EOL;
    }
$ht .= '</div>' . PHP_EOL;

    return $ht;
}




function renderInputText($input) {
    $input = array_merge(PROTO_INPUT, $input);
    if (isset($input['gcPerms']))
        foreach ($input['gcPerms'] as $perm)
            $input['cssClass'] .= ' hide-perm-' . $perm;

    $input = array_merge(PROTO_INPUT, $input);

    $css = $input['lang'] ? 'input-wrap-lang hide-lang-' . $input['lang'] : '';

$ht =  '<div class="input-wrap pad ' . h($css) . '">' . PHP_EOL;

    switch ($input['type']) {
        case 'textarea':
        case 'trix':
$ht .= '    <div class="input-label">' . t($input['label']) . '<span class="input-label-lang"> ' . $input['lang'] . '</span></div>' . PHP_EOL;
$ht .= '    <div class="content">' . h(strip_tags($input['valueFromDb'], ALLOWED_TAGS)) . '</div>' . PHP_EOL;
            break;

        case 'timestamp':
$ht .= '    <div class="input-label">' . t($input['label']) . ' <span class="input-label-lang">' . $input['lang'] . '</span></div>' . PHP_EOL;
$ht .= '    <div class="content">' . h($input['valueFromDb']) . '</div>' . PHP_EOL;
            break;

        case 'status':
        case 'radio':
        case 'select':
$ht .= '    <div class="input-label">' . t($input['label']) . ' <span class="input-label-lang">' . $input['lang'] . '</span></div>' . PHP_EOL;
$ht .= '    <div class="content">' . h($input['options'][$input['valueFromDb']]['label']) . '</div>' . PHP_EOL;
            break;

        case 'password':
        case 'none':
            break;

        default:
$ht .= '    <div class="input-label">' . t($input['label']) . ' <span class="input-label-lang">' . $input['lang'] . '</span></div>' . PHP_EOL;
$ht .= '    <div class="content">' . h($input['valueFromDb']) . '</div>' . PHP_EOL;
            break;
    }

$ht .= '</div>' . PHP_EOL;

return $ht;
}



// form input creators


function renderInput(App $app, $input) {
    $input = array_merge(PROTO_INPUT, $input);
    if ($input['type'] == 'none') return;

    $input['infos'] = infos('form', $input['name']);

    $css = 'input-wrap input-wrap-' . $input['type'];
    $css .= $input['lang'] ? ' input-wrap-lang hide-lang-' . $input['lang'] : '';
    $css .= ' pad';

    if (isset($input['gcPerms']))
        foreach ($input['gcPerms'] as $perm)
            $input['cssClass'] .= ' hide-perm-' . $perm;

    $css .= ' ' . $input['cssClass'];

    $css .= empty($input['errors']) ? '' : ' input-wrap-errors';
    $css .= empty($input['infos']) ? '' : ' input-wrap-infos';

$ht =  '<div class="' . h($css) . '">' . PHP_EOL;
$ht .= '    <div class="input-label">' .
              '<span class="input-title">' . t($input['label']) . ' <span class="input-label-lang">' . $input['lang'] . '</span></span><br> ' .
              '<span class="input-changed">' . t('Modified') . '.</span> ' .
              '<button type="button" class="input-initial fake">' . t('initial') . '</button> ' .
              '<button type="button" class="input-initial-undo fake">' . t('undo') . '</button> ' .
           '</div>' . PHP_EOL;

    switch ($input['type']) {

        case 'password':
            $input['value'] = '';
            $input['valueFromDb'] = '';
            $ht .= getBasicInput($input) . PHP_EOL;
            break;

        case 'slug':
            $ht .= getSlugInput($input) . PHP_EOL;
            break;

        case 'slugImage':
            $ht .= getSlugImageInput($input) . PHP_EOL;
            break;

        case 'raw':
            $ht .= getRawInput($input) . PHP_EOL;
            break;

        case 'text':
            $ht .= getTextInput($input) . PHP_EOL;
            break;

        case 'email':
        case 'url':
        case 'number':
            $ht .= getBasicInput($input) . PHP_EOL;
            break;

        case 'trix':
            $ht .= getTrixInput($input) . PHP_EOL;
            break;

        case 'textarea':
            $ht .= getBasicInput($input) . PHP_EOL;
            break;

        case 'datetime':
        case 'timestamp':
            $ht .= getTimestampInput($input) . PHP_EOL;
            break;

        case 'date':
            $ht .= getDateInput($input) . PHP_EOL;
            break;

        case 'time':
            $ht .= getTimeInput($input) . PHP_EOL;
            break;

        case 'status':
        case 'radio':
            $ht .= getRadioInput($input) . PHP_EOL;
            break;

        case 'select':
            $ht .= getSelectInput($input) . PHP_EOL;
            break;

        case 'image':
            $ht .= getImageInput($input) . PHP_EOL;
            break;

        case 'importerJsonld':
            $ht .= getImporterJsonldInput($input) . PHP_EOL;
            break;

        case 'importerYoutube':
            $ht .= getImporterYoutubeInput($input) . PHP_EOL;
            break;

        default:
            $input['errors'][] = 'Invalid input type: ' . h($input['type']);
            break;

    }

    if ($input['type'] == 'slugImage') {
        $imgType = t($input['options']['imgType'] ?? '');
        if ($img = $app->imageGet($input['value'] ?? '', ['w' => 256, 'h' => 256, 'fit' => 'cover', 'version' => 'mtime'], false)) {
$ht .= '    <button type="button"  class="slugImage ratio center" onclick="gjImageSelectorOpen(this, \'' . h($imgType) . '\')">' . PHP_EOL;
$ht .= gImageRenderReflowSpacer($img['w'], $img['h']) . PHP_EOL;
$ht .= gImageRender($img, 'onerror="gjImageResizeRequest(this, event)"') . PHP_EOL;
$ht .= '    </button>' . PHP_EOL;
        } else {
$ht .= '    <button type="button" class="slugImage ratio center empty" onclick="gjImageSelectorOpen(this, \'' . h($imgType) . '\')">' . PHP_EOL;
$ht .= gImageRenderReflowSpacer(100, 75) . PHP_EOL;
$ht .= '    <img src="/edit/gfx/no-photo.png" onerror="gjImageResizeRequest(this, event)">' . PHP_EOL;
$ht .= '    </button>' . PHP_EOL;
        }
    }

$ht .= '    <ul class="input-errors">' . PHP_EOL;
    foreach ($input['errors'] as $error) {
$ht .= '        <li class="input-error">' . h($error) . '</li>' . PHP_EOL;
    }
$ht .= '    </ul>' . PHP_EOL;

$ht .= '    <ul class="input-infos">' . PHP_EOL;
    foreach ($input['infos'] as $info) {
$ht .= '        <li class="input-info">' . h($info) . '</li>' . PHP_EOL;
    }
$ht .= '    </ul>' . PHP_EOL;

$ht .= '</div>' . PHP_EOL;

    return $ht;
}




// inputs

function getRawInput($input) {
    $r = '    <input class="input-text" type="' . $input['type'] . '" name="' . $input['name'] . '" value="' . htmlspecialchars($input['value'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5) . '" onkeydown="disableEnterKey(event)" oninput="gjInputChange(this, event)"';

    foreach ($input['options'] as $optionName => $optionValue) {
        $r .= ' ' . $optionName . '="' . $optionValue . '"';
    }

    if ($input['disabled']) $r .= ' disabled';

    return $r . '>';
}




function getBasicInput($input) {
    $r = '    <input class="input-text" type="' . $input['type'] . '" name="' . $input['name'] . '" value="' . $input['value'] . '" onkeydown="disableEnterKey(event)" oninput="gjInputChange(this, event)"';

    foreach ($input['options'] as $optionName => $optionValue) {
        $r .= ' ' . $optionName . '="' . $optionValue . '"';
    }

    if ($input['disabled']) $r .= ' disabled';

    return $r . '>';
}




function getTextInput($input) {
    $r = '    <textarea class="input-text" name="' . $input['name'] . '" rows="1" wrap="soft" onkeydown="disableEnterKey(event)" oninput="textareaAutoGrow(this); gjInputChange(this, event)"';

    foreach ($input['options'] as $optionName => $option) {
        $r .= ' ' . $optionName . '="' . $option . '"';
    }

    if ($input['disabled']) $r .= ' disabled';

    return $r . '>' . $input['value'] . '</textarea>';
}





function getImageInput($input) {
    $r =  '    <div>' . PHP_EOL;
    $r .= '        <input type="hidden" name="MAX_FILE_SIZE" value="20000000" />' . PHP_EOL;
    $r .= '        <input pattern="[a-z0-9\-]*" class="input-file input-image btn active" type="file" accept="image/jpeg,image/png" name="' . $input['name'] . '" oninput="gjInputChange(this, event)"';
    if ($input['disabled']) $r .= ' disabled';
    if (isset($input['options']['multiple'])) $r .= ' multiple';
    $r .= '>' . PHP_EOL;
    $r .= '    </div>' . PHP_EOL;
    return $r;
}




function getSlugInput($input) {
    $r = '    <textarea pattern="[a-z0-9\-]*" class="input-text input-slug" name="' . $input['name'] . '" rows="1" wrap="soft" onkeydown="disableEnterKey(event)" oninput="gjInputFormat(this, \'slug\'); textareaAutoGrow(this); gjInputChange(this, event)"';

    foreach ($input['options'] as $optionName => $optionValue)
        $r .= ' ' . $optionName . '="' . $optionValue . '"';

    if ($input['disabled']) $r .= ' disabled';

    return $r . '>' . $input['value'] . '</textarea>';
}




function getSlugImageInput($input) {
    $r = '    <textarea pattern="[a-z0-9\-]*" class="input-text input-slug" name="' . $input['name'] . '" rows="1" wrap="soft" onkeydown="disableEnterKey(event)" oninput="gjInputFormat(this, \'slug\'); textareaAutoGrow(this); gjInputChange(this, event)"';

    foreach ($input['options'] as $optionName => $optionValue)
        $r .= ' ' . $optionName . '="' . $optionValue . '"';

    if ($input['disabled']) $r .= ' disabled';

    return $r . '>' . $input['value'] . '</textarea>';
}




function getRadioInput($input) {
    $r = '';
    foreach ($input['options'] as $optionValue => $option) {
        $css = 'btn input-radio btn-pill';
        $attr = '';

        if (isset($option['cssClass'])) $css .= ' ' . $option['cssClass'];
        if ($optionValue == $input['value']) {
            $attr .= ' checked';
            $css .= ' active';
        }
        if (isset($option['disabled'])) $attr .= ' disabled';

        $r .= '    <label class="' . h($css) . '">' . PHP_EOL;
        $r .= '        <input type="radio" name="' . $input['name'] . '" value="' . $optionValue . '"' . h($attr) . ' onchange="gjInputChange(this, event)" onkeydown="disableEnterKey(event)">' . PHP_EOL;
        $r .= '        ' . t($option['label']) . PHP_EOL;
        $r .= '    </label>' . PHP_EOL;
    }
    return $r;
}




function getSelectInput($input) {
    $r = '    <select class="input-select" name="' . $input['name'] . '"' . ($input['disabled'] ? ' disabled' : '') . ' onchange="gjInputChange(this, event)" onblur="gjInputChange(this, event)">' . PHP_EOL;

    $selectedFound = false;
    foreach ($input['options'] as $optionValue => $option) {
        if ($optionValue == $input['value']) {
            $selectedFound = true;
            break;
        }
    }

    $i = 0;
    foreach ($input['options'] as $optionValue => $option) {
        if ($selectedFound) {
            $selected = ($optionValue == $input['value']) ? ' selected' : '';
        } else if ($i == 0) {
            $selected = ' selected';
        } else {
            $selected = '';
        }
        $r .= '        <option value="' . h($optionValue) . '"' . $selected . '>' . t($option['label']) . '</option>' . PHP_EOL;
        $i++;
    }

    $r .= '    </select>';

    return $r;
}




function getTextareaInput($input) {
    $r = '    <textarea class="input-text input-textarea" name="' . $input['name'] . '" rows="1" wrap="soft" oninput="textareaAutoGrow(this); gjInputChange(this, event)"';

    foreach ($input['options'] as $optionName => $option) {
        $r .= ' ' . $optionName . '="' . $option . '"';
    }

    if ($input['disabled']) $r .= ' disabled';

    return $r . '>' . $input['value'] . '</textarea>';
}




function getTrixInput($input) {
    $r = '    <input type="hidden" class="input-trix" name="' . $input['name'] . '" id="' . $input['name'] . '" value="' . h($input['value']) . '" oninput="gjInputChange(this, event)" onchange="gjInputChange(this, event)"';

    if ($input['disabled']) $r .= ' disabled';

    $r .= '>';

    return $r . '    <trix-editor lang="' . $input['lang'] . '" input="' . $input['name'] . '"></trix-editor>';
}




function getTimestampInput($input) {
    if (substr($input['value'], 16) == ':00') $input['value'] = substr($input['value'], 0, 16);
    $r = '    <input class="input-text input-timestamp" type="text" name="' . $input['name'] . '" value="' . h($input['value']) . '" onkeydown="disableEnterKey(event)" oninput="gjInputChange(this, event)"';

    foreach ($input['options'] as $optionName => $option) {
        $r .= ' ' . $optionName . '="' . $option . '"';
    }

    if ($input['disabled']) $r .= ' disabled';

    return $r . '>';
}




function getDateInput($input) {
    $r = '    <input class="input-text input-date" type="text" name="' . $input['name'] . '" value="' . h($input['value']) . '" onkeydown="disableEnterKey(event); gjInputMod(this,event);" oninput="gjInputFormat(this, \'date\'); gjInputChange(this, event)"';

    foreach ($input['options'] as $optionName => $option) {
        $r .= ' ' . $optionName . '="' . $option . '"';
    }

    if ($input['disabled']) $r .= ' disabled';

    return $r . '>';
}




function getTimeInput($input) {
    if (substr($input['value'], 5) == ':00') $input['value'] = substr($input['value'], 0, 5);
    $r = '    <input class="input-text input-time" type="text" name="' . $input['name'] . '" value="' . h($input['value']) . '" onkeydown="disableEnterKey(event); gjInputMod(this,event);" oninput="gjInputFormat(this, \'time\'); gjInputChange(this, event)"';

    foreach ($input['options'] as $optionName => $option) {
        $r .= ' ' . $optionName . '="' . $option . '"';
    }

    if ($input['disabled']) $r .= ' disabled';

    return $r . '>';
}




function getImporterJsonldInput($input) {
    $r  = '    <textarea class="input-text" rows="1" wrap="soft" onkeydown="disableEnterKey(event)" oninput="textareaAutoGrow(this);"></textarea>' . PHP_EOL;
    $r .= '    <button type="button" class="btn btn-blue btn-pill rr" onclick="gjImportJsonld(this, event)">' . t('Import') . '</button>' . PHP_EOL;
    $r .= '    <script type="text/javascript">var importRelationsJsonld = ' . json_encode($input['options']) . '</script>' . PHP_EOL;
    return $r;
}




function getImporterYoutubeInput($input) {
    $r  = '    <textarea class="input-text" rows="1" wrap="soft" onkeydown="disableEnterKey(event)" oninput="textareaAutoGrow(this);">https://www.youtube.com/watch?v=hIBlo3ZWdYk</textarea>' . PHP_EOL;
    $r .= '    <button type="button" class="btn btn-blue btn-pill rr" onclick="gjImportYoutube(this, event)">' . t('Import') . '</button>' . PHP_EOL;
    $r .= '    <script type="text/javascript">var importRelationsYoutube = ' . json_encode($input['options']) . '</script>' . PHP_EOL;
    return $r;
}
