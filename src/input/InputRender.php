<?php


namespace GalaxiaEditor\input;


use Galaxia\App;
use Galaxia\AppImage;
use Galaxia\Director;
use Galaxia\Flash;


class InputRender {

    public static function form($action = '', $id = '', $classes = '') {
        if ($action) $action = ' action="' . h($action) . '"';
        if ($id) $id = ' id="' . h($id) . '"';
        if ($classes) $classes = ' class="' . h($classes) . '"';
        $form = '<form' . $action . $id . $classes . ' method="post" enctype="multipart/form-data">' . PHP_EOL;
        $form .= '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">' . PHP_EOL;
        echo $form;
    }

    public static function renderFormEnd() {
        echo '</form>';
    }




    public static function status($input) {
        $input = array_merge(Input::PROTO_INPUT, $input);

        if (isset($input['gcPerms']))
            foreach ($input['gcPerms'] as $perm)
                $input['cssClass'] .= ' hide-perm-' . $perm;

        $css = 'paper-header-status input-wrap' . $input['cssClass'];
        $css .= empty($input['errors']) ? '' : ' input-wrap-errors';

$ht = '<div class="' . $css . '">' . PHP_EOL;
$ht .= InputRender::getRadioInput($input);
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




    public static function renderInputText($input) {
        $input = array_merge(Input::PROTO_INPUT, $input);
        if (isset($input['gcPerms']))
            foreach ($input['gcPerms'] as $perm)
                $input['cssClass'] .= ' hide-perm-' . $perm;

        $input = array_merge(Input::PROTO_INPUT, $input);

        $css = $input['lang'] ? 'hide-lang-' . $input['lang'] : '';

$ht = '<div class="input-wrap pad ' . h($css) . '">' . PHP_EOL;

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




    public static function renderInput(App $app, $input) {
        $input = array_merge(Input::PROTO_INPUT, $input);
        if ($input['type'] == 'none') return;

        $input['infos'] = Flash::infos('form', $input['name']);

        $css = 'input-wrap input-wrap-' . $input['type'];
        $css .= $input['lang'] ? ' hide-lang-' . $input['lang'] : '';
        $css .= ' pad';

        if (isset($input['gcPerms']))
            foreach ($input['gcPerms'] as $perm)
                $input['cssClass'] .= ' hide-perm-' . $perm;

        $css .= ' ' . $input['cssClass'];

        $css .= empty($input['errors']) ? '' : ' input-wrap-errors';
        $css .= empty($input['infos']) ? '' : ' input-wrap-infos';

        if (substr($input['label'], -3, 1) == '_') $input['label'] = substr($input['label'], 0, -3);
        if ($input['translate']) $input['label'] = t($input['label']);


        $len    = '';
        $br     = '';
        $minlen = $input['options']['minlength'] ?? '';
        $maxlen = $input['options']['maxlength'] ?? '';
        if ($minlen > 0) $minlen = '<span class="input-min">' . h($minlen) . '</span> < ';
        if ($maxlen > 0) $maxlen = ' < <span class="input-max">' . h($maxlen) . '</span> ';
        if ($input['type'] == 'trix') {
            $len = '<span class="input-len" title="' . t('Number of letters ❖ Number of words') . '">' . ($input['type'] == 'trix' ? '' : mb_strlen($input['value'])) . '</span> ';
            $br  = '<br>';
        } else if ($minlen || $maxlen) {
            $len = '<span class="input-len">' . ($input['type'] == 'trix' ? '' : mb_strlen($input['value'])) . '</span> ';
            $br  = '<br>';
        }

        $titleTitle = (Director::isDev()) ? (h($input['prefix']) ?? h($input['name'])) : '';

$ht = '<div class="' . h($css) . '">' . PHP_EOL;
$ht .= '    <div class="input-label">' .
               '<span class="input-title" title="' . h($titleTitle) . '">' . $input['label'] . ' <span class="input-label-lang">' . $input['lang'] . '</span></span><br> ' .
               $minlen .
               $len .
               $maxlen .
               $br .
               '<span class="input-changed">' . t('Modified') . '.</span> ' .
               '<button type="button" class="input-initial fake">' . t('initial') . '</button> ' .
               '<button type="button" class="input-initial-undo fake">' . t('undo') . '</button> ' .
               '</div>' . PHP_EOL;

        switch ($input['type']) {

            case 'password':
                $input['value']       = '';
                $input['valueFromDb'] = '';

                $ht .= InputRender::getBasicInput($input) . PHP_EOL;
                break;

            case 'slug':
                $ht .= InputRender::getSlugInput($input) . PHP_EOL;
                break;

            case 'slugImage':
                $ht .= InputRender::getSlugImageInput($input) . PHP_EOL;
                break;

            case 'raw':
                $ht .= InputRender::getRawInput($input) . PHP_EOL;
                break;

            case 'text':
                $ht .= InputRender::getTextInput($input) . PHP_EOL;
                break;

            case 'email':
            case 'url':
            case 'number':
                $ht .= InputRender::getBasicInput($input) . PHP_EOL;
                break;

            case 'trix':
                $ht .= InputRender::getTrixInput($input) . PHP_EOL;
                break;

            case 'textarea':
                $ht .= InputRender::getTextareaInput($input) . PHP_EOL;
                break;

            case 'datetime':
            case 'timestamp':
                $ht .= InputRender::getTimestampInput($input) . PHP_EOL;
                break;

            case 'date':
                $ht .= InputRender::getDateInput($input) . PHP_EOL;
                break;

            case 'time':
                $ht .= InputRender::getTimeInput($input) . PHP_EOL;
                break;

            case 'status':
            case 'radio':
                $ht .= InputRender::getRadioInput($input) . PHP_EOL;
                break;

            case 'select':
                $ht .= InputRender::getSelectInput($input) . PHP_EOL;
                break;

            case 'image':
                $ht .= InputRender::getImageInput($input) . PHP_EOL;
                break;

            case 'importerJsonld':
                $ht .= InputRender::getImporterJsonldInput($input) . PHP_EOL;
                break;

            case 'importerYoutube':
                $ht .= InputRender::getImporterYoutubeInput($input) . PHP_EOL;
                break;

            case 'importerVimeo':
                $ht .= InputRender::getImporterVimeoInput($input) . PHP_EOL;
                break;

            default:
                $input['errors'][] = 'Invalid input type: ' . h($input['type']);
                break;

        }

        if ($input['type'] == 'slugImage') {
            $imgType = t($input['options']['imgType'] ?? '');
            if ($img = $app->imageGet($input['value'] ?? '', ['w' => 256, 'h' => 256, 'fit' => 'cover', 'version' => 'mtime'], false)) {
$ht .= '    <button type="button" class="slugImage figure" data-imgtype="' . h($imgType) . '">' . PHP_EOL;
$ht .= '        ' . AppImage::render($img) . PHP_EOL;
$ht .= '    </button>' . PHP_EOL;
            } else {
$ht .= '    <button type="button" class="slugImage figure empty" data-imgtype="' . h($imgType) . '">' . PHP_EOL;
$ht .= '        <img alt="" src="/edit/gfx/no-photo-add.png">' . PHP_EOL;
$ht .= '    </button>' . PHP_EOL;
            }
        }

$ht .= '    <ul class="input-footer input-errors">' . PHP_EOL;
        foreach ($input['errors'] as $error) {
$ht .= '        <li class="input-error">' . h($error) . '</li>' . PHP_EOL;
        }
$ht .= '    </ul>' . PHP_EOL;

$ht .= '    <ul class="input-footer input-infos">' . PHP_EOL;
        foreach ($input['infos'] as $info) {
$ht .= '        <li class="input-info">' . h($info) . '</li>' . PHP_EOL;
        }
$ht .= '    </ul>' . PHP_EOL;

$ht .= '</div>' . PHP_EOL;

        return $ht;
    }




    public static function getRawInput($input) {
$r = '    <input class="input-text" type="' . $input['type'] . '" name="' . $input['name'] . '" value="' . htmlspecialchars($input['value'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5) . '"';

        foreach ($input['options'] as $optionName => $optionValue) {
$r .= ' ' . $optionName . '="' . $optionValue . '"';
        }

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>';
    }




    public static function getBasicInput($input) {
$r = '    <input class="input-text" type="' . $input['type'] . '" name="' . $input['name'] . '" value="' . $input['value'] . '"';

        foreach ($input['options'] as $optionName => $optionValue) {
$r .= ' ' . $optionName . '="' . $optionValue . '"';
        }

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>';
    }




    public static function getTextInput($input) {
$r = '    <textarea class="input-text" name="' . $input['name'] . '" rows="1" wrap="soft"';

        foreach ($input['options'] as $optionName => $option) {
$r .= ' ' . $optionName . '="' . $option . '"';
        }

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>' . $input['value'] . '</textarea>';
    }

    public static function getImageInput($input) {

        $maxMemory   = sizeShorthandToInt(ini_get('memory_limit'));
        $maxPostSize = sizeShorthandToInt(ini_get('post_max_size'));
        $maxTotal    = min($maxMemory, $maxPostSize);

        $maxUploadSize  = min($maxTotal, sizeShorthandToInt(ini_get('upload_max_filesize')));
        $maxUploadFiles = ini_get('max_file_uploads');

        $dataSizes = ' data-maxtotal="' . $maxTotal . '" data-maxsize="' . $maxUploadSize . '" data-maxcount="' . $maxUploadFiles . '"';

        $multiple = ($input['options']['multiple'] ?? false) ? ' multiple' : '';
        $disabled = ($maxUploadSize < 1 || $input['disabled']) ? ' disabled' : '';

$r = '';
$r .= '    <div>' . PHP_EOL;
$r .= '        <input type="hidden" name="MAX_FILE_SIZE" value="' . $maxUploadSize . '"' . $disabled . '>' . PHP_EOL;
$r .= '        <input name="' . $input['name'] . '" pattern="[a-z0-9\-]*" class="input-file input-image btn active" type="file" accept="image/jpeg,image/png"' . $dataSizes . $multiple . $disabled . '>' . PHP_EOL;
$r .= '        <div class="info">' . PHP_EOL;
$r .= '            <div>' . sprintf(unsafet('Total size: <span class="maxtotal">0 B</span> / %s'), gFilesize($maxTotal)) . '</div>' . PHP_EOL;
$r .= '            <div>' . sprintf(unsafet('Max file size: <span class="maxsize">0 B</span> / %s'), gFilesize($maxUploadSize)) . '</div>' . PHP_EOL;
$r .= '            <div>' . sprintf(unsafet('Number of files: <span class="maxcount">0</span> / %s'), $maxUploadFiles) . '</div>' . PHP_EOL;
$r .= '        </div>' . PHP_EOL;
$r .= '        <ul class="upload-files">' . PHP_EOL;
$r .= '        </ul>' . PHP_EOL;
$r .= '    </div>' . PHP_EOL;

        return $r;
    }




    public static function getSlugInput($input) {
$r = '    <textarea pattern="[a-z0-9\-]*" class="input-text input-slug" name="' . $input['name'] . '" rows="1" wrap="soft"';

        foreach ($input['options'] as $optionName => $optionValue)
$r .= ' ' . $optionName . '="' . $optionValue . '"';

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>' . $input['value'] . '</textarea>';
    }




    public static function getSlugImageInput($input) {
$r = '    <textarea pattern="[a-z0-9\-]*" class="input-text input-slug input-slugImg" name="' . $input['name'] . '" rows="1" wrap="soft"';

        foreach ($input['options'] as $optionName => $optionValue)
$r .= ' ' . $optionName . '="' . $optionValue . '"';

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>' . $input['value'] . '</textarea>';
    }




    public static function getRadioInput($input) {
$r = '';
        foreach ($input['options'] as $optionValue => $option) {
            $css  = 'btn input-radio btn-pill';
            $attr = '';

            if (isset($option['cssClass'])) $css .= ' ' . $option['cssClass'];
            if ($optionValue == $input['value']) {
                $attr .= ' checked';
                $css  .= ' active';
            }
            if (isset($option['disabled'])) {
                $attr .= ' disabled';
                $css  .= ' disabled';
            }

$r .= '    <label class="' . h($css) . '">' . PHP_EOL;
$r .= '        <input type="radio" name="' . $input['name'] . '" value="' . $optionValue . '"' . h($attr) . '>' . PHP_EOL;
$r .= '        ' . t($option['label']) . PHP_EOL;
$r .= '    </label>' . PHP_EOL;
        }

        return $r;
    }




    public static function getSelectInput($input) {
$r = '    <select class="input-select" name="' . $input['name'] . '"' . ($input['disabled'] ? ' disabled' : '') . '>' . PHP_EOL;

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
$r .= '        <option value="' . h($optionValue) . '"' . $selected . '>' . t($option['label'] ?? '') . '</option>' . PHP_EOL;
            $i++;
        }

$r .= '    </select>';

        return $r;
    }




    public static function getTextareaInput($input) {
$r = '    <textarea class="input-text input-textarea" name="' . $input['name'] . '" rows="1" wrap="soft"';

        foreach ($input['options'] as $optionName => $option) {
$r .= ' ' . $optionName . '="' . $option . '"';
        }

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>' . $input['value'] . '</textarea>';
    }




    public static function getTrixInput($input) {
$r = '    <input type="hidden" class="input-trix" name="' . $input['name'] . '" id="' . $input['name'] . '" value="' . h($input['value']) . '"';

        if ($input['disabled']) $r .= ' disabled';

$r .= '>';

        return $r . '    <trix-editor lang="' . $input['lang'] . '" input="' . $input['name'] . '"></trix-editor>';
    }




    public static function getTimestampInput($input) {
        if (substr($input['value'], 16) == ':00') $input['value'] = substr($input['value'], 0, 16);
$r = '    <input class="input-text input-timestamp" type="text" name="' . $input['name'] . '" value="' . h($input['value']) . '"';

        foreach ($input['options'] as $optionName => $option) {
$r .= ' ' . $optionName . '="' . $option . '"';
        }

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>';
    }




    public static function getDateInput($input) {
$r = '    <input class="input-text input-date" type="text" name="' . $input['name'] . '" value="' . h($input['value']) . '"';

        foreach ($input['options'] as $optionName => $option) {
$r .= ' ' . $optionName . '="' . $option . '"';
        }

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>';
    }




    public static function getTimeInput($input) {
        if (substr($input['value'], 5) == ':00') $input['value'] = substr($input['value'], 0, 5);
$r = '    <input class="input-text input-time" type="text" name="' . $input['name'] . '" value="' . h($input['value']) . '"';

        foreach ($input['options'] as $optionName => $option) {
$r .= ' ' . $optionName . '="' . $option . '"';
        }

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>';
    }




    public static function getImporterJsonldInput($input) {
$r = '    <textarea class="input-text" rows="1" wrap="soft"></textarea>' . PHP_EOL;
$r .= '    <button type="button" class="btn btn-blue btn-pill rr scrape-jsonld">' . t('Import') . '</button>' . PHP_EOL;
$r .= '    <script type="text/javascript">var importRelationsJsonld = ' . json_encode($input['options']) . '</script>' . PHP_EOL;

        return $r;
    }




    public static function getImporterYoutubeInput($input) {
$r = '    <textarea class="input-text" rows="1" wrap="soft">' . $input['value'] . '</textarea>' . PHP_EOL;
$r .= '    <button type="button" class="btn btn-blue btn-pill rr scrape-youtube">' . t('Import') . '</button>' . PHP_EOL;
$r .= '    <script type="text/javascript">var importRelationsYoutube = ' . json_encode($input['options']) . '</script>' . PHP_EOL;

        return $r;
    }




    public static function getImporterVimeoInput($input) {
$r = '    <textarea class="input-text" rows="1" wrap="soft">' . $input['value'] . '</textarea>' . PHP_EOL;
$r .= '    <button type="button" class="btn btn-blue btn-pill rr scrape-vimeo">' . t('Import') . '</button>' . PHP_EOL;
$r .= '    <script type="text/javascript">var importRelationsVimeo = ' . json_encode($input['options']) . '</script>' . PHP_EOL;

        return $r;
    }




}
