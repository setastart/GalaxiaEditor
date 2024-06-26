<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace GalaxiaEditor\input;


use Galaxia\AppImage;
use Galaxia\G;
use Galaxia\Flash;
use Galaxia\Text;
use GalaxiaEditor\E;


class InputRender {

    static function form($action = '', $id = '', $classes = ''): void {
        if ($action) $action = ' action="' . Text::h($action) . '"';
        if ($id) $id = ' id="' . Text::h($id) . '"';
        if ($classes) $classes = ' class="' . Text::h($classes) . '"';
        $form = '<form' . $action . $id . $classes . ' method="post" enctype="multipart/form-data">' . PHP_EOL;
        $form .= '<input type="hidden" name="csrf" value="' . Text::h($_SESSION['csrf']) . '">' . PHP_EOL;
        echo $form;
    }

    static function renderFormEnd(): void {
        echo '</form>';
    }




    static function status($input): string {
        $input = array_merge(Input::PROTO_INPUT, $input);

        if (isset($input['gcPerms']))
            foreach ($input['gcPerms'] as $perm)
                $input['cssClass'] .= ' hide-perm-' . $perm;

        $css = 'paper-header-status input-wrap' . $input['cssClass'];
        $css .= empty($input['errors']) ? '' : ' input-wrap-errors';

$ht = '<div class="' . $css . '">' . PHP_EOL;
$ht .= InputRender::getRadioInput($input);

        if (!empty($input['errors'])) {
$ht .= '    <ul class="input-errors">' . PHP_EOL;
            foreach ($input['errors'] as $error) {
$ht .= '        <li class="input-error">' . Text::h($error) . '</div>' . PHP_EOL;
            }
$ht .= '    </ul>' . PHP_EOL;
        }
$ht .= '</div>' . PHP_EOL;

        return $ht;
    }




    static function renderInputText($input): string {
        $input = array_merge(Input::PROTO_INPUT, $input);
        if (isset($input['gcPerms']))
            foreach ($input['gcPerms'] as $perm)
                $input['cssClass'] .= ' hide-perm-' . $perm;

        $input = array_merge(Input::PROTO_INPUT, $input);

        $css = $input['lang'] ? 'hide-lang-' . $input['lang'] : '';

$ht = '<div class="input-wrap pad ' . Text::h($css) . '">' . PHP_EOL;

        switch ($input['type']) {
            case 'textarea':
            case 'rico':
$ht .= '    <div class="input-label">' . Text::t($input['label']) . '<span class="input-label-lang"> ' . $input['lang'] . '</span></div>' . PHP_EOL;
$ht .= '    <div class="content">' . Text::h(strip_tags($input['valueFromDb'], Text::ALLOWED_TAGS)) . '</div>' . PHP_EOL;
                break;

            case 'status':
            case 'radio':
            case 'select':
$ht .= '    <div class="input-label">' . Text::t($input['label']) . ' <span class="input-label-lang">' . $input['lang'] . '</span></div>' . PHP_EOL;
$ht .= '    <div class="content">' . Text::h($input['options'][$input['valueFromDb']]['label']) . '</div>' . PHP_EOL;
                break;

            case 'password':
            case 'none':
                break;

            case 'timestamp':
            default:
$ht .= '    <div class="input-label">' . Text::t($input['label']) . ' <span class="input-label-lang">' . $input['lang'] . '</span></div>' . PHP_EOL;
$ht .= '    <div class="content">' . Text::h($input['valueFromDb']) . '</div>' . PHP_EOL;
                break;
        }

$ht .= '</div>' . PHP_EOL;

        return $ht;
    }




    static function renderInput($input): string {
        $input = array_merge(Input::PROTO_INPUT, $input);
        if ($input['type'] == 'none') return '';

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
        if ($input['translate']) $input['label'] = Text::t($input['label']);


        $len    = '';
        $minlen = $input['options']['minlength'] ?? 0;
        $maxlen = $input['options']['maxlength'] ?? 0;
        $minlen = $minlen ?: '';
        $maxlen = $maxlen ?: '';
        if ($minlen) $minlen = '<span class="input-min">' . Text::h($minlen) . '</span><span class="tiny">﹤</span>';
        if ($maxlen) $maxlen = '<span class="tiny">﹤</span><span class="input-max">' . Text::h($maxlen) . '</span> ';
        if ($input['type'] == 'rico') {
            $len = '<span class="input-len" title="' . Text::t('Number of letters ❖ Number of words') . '">' . ($input['type'] == 'rico' ? '' : mb_strlen($input['value'] ?? '')) . '</span>';
        } else if ($minlen || $maxlen) {
            $len = '<span class="input-len">' . mb_strlen($input['value'] ?? '') . '</span>';
        }

        $titleTitle = (G::isDev()) ? (Text::h($input['prefix']) ?? Text::h($input['name'])) : '';
        $translate = (substr($input['nameFromDb'], -3, 1) == '_' || substr($input['name'], -3, 1) == '_') && E::$hookTranslate && in_array($input['type'], ['text', 'textarea', 'rico']);

$ht = '<div class="' . Text::h($css) . '">' . PHP_EOL;
$ht .= '    <div class="input-label">' .
               '<div><span class="input-title" title="' . Text::h($titleTitle) . '">' . $input['label'] . '</span> <span class="input-label-lang">' . $input['lang'] . '</span></div>' .
               "<div class=\"grey\">{$minlen}{$len}{$maxlen}</div>" .
               // '<div>' .
               '<button type="button" class="input-initial fake">' . Text::t('initial') . '</button> ' .
               '<button type="button" class="input-initial-undo fake">' . Text::t('undo') . '</button> ' .
                ($translate ? '<button type="button" class="input-translate fake">' . Text::t('translate') . '</button> ' : '') .
                ($input['type'] == 'date' ? '<button type="button" class="input-calendar fake">' . Text::t('calendar') . '</button> ' : '') .
       // '</div>' .
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

            case 'url':
            case 'email':
            case 'number':
$ht .= InputRender::getBasicInput($input) . PHP_EOL;
                break;

            case 'rico':
$ht .= InputRender::getRicoInput($input) . PHP_EOL;
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
                $input['errors'][] = 'Invalid input type: ' . Text::h($input['type']);
                break;

        }

        if ($input['type'] == 'slugImage') {
            $imgType = Text::t($input['options']['imgType'] ?? '');
            if ($img = AppImage::imageGet($input['value'] ?? '', ['w' => 256, 'h' => 256, 'version' => 'mtime', 'loading' => false], false)) {
$ht .= '    <button type="button" class="slugImage figure" data-imgtype="' . Text::h($imgType) . '">' . PHP_EOL;
$ht .= '        ' . AppImage::render($img) . PHP_EOL;
$ht .= '    </button>' . PHP_EOL;
            } else {
$ht .= '    <button type="button" class="slugImage figure empty" data-imgtype="' . Text::h($imgType) . '">' . PHP_EOL;
$ht .= '        <img alt="" src="/edit/gfx/btn/no-photo-add.png">' . PHP_EOL;
$ht .= '    </button>' . PHP_EOL;
            }
        }

$ht .= '    <ul class="input-footer input-errors">' . PHP_EOL;
        foreach ($input['errors'] as $error) {
$ht .= '        <li class="input-error">' . Text::h($error) . '</li>' . PHP_EOL;
        }
$ht .= '    </ul>' . PHP_EOL;

$ht .= '    <ul class="input-footer input-infos">' . PHP_EOL;
        foreach ($input['infos'] as $info) {
$ht .= '        <li class="input-info">' . Text::h($info) . '</li>' . PHP_EOL;
        }
$ht .= '    </ul>' . PHP_EOL;

$ht .= '</div>' . PHP_EOL;

        return $ht;
    }




    static function getRawInput($input): string {
$r = '    <input class="input-text" type="' . $input['type'] . '" name="' . $input['name'] . '" value="' . htmlspecialchars($input['value'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8', false) . '"';

        foreach ($input['options'] as $optionName => $optionValue) {
$r .= ' ' . $optionName . '="' . $optionValue . '"';
        }

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>';
    }




    static function getBasicInput($input): string {
$r = '    <input class="input-text" type="' . $input['type'] . '" name="' . $input['name'] . '" value="' . $input['value'] . '"';

        foreach ($input['options'] as $optionName => $optionValue) {
$r .= ' ' . $optionName . '="' . $optionValue . '"';
        }

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>';
    }




    static function getTextInput($input): string {
$r = '    <textarea class="input-text" name="' . $input['name'] . '" rows="1" wrap="soft"';

        foreach ($input['options'] as $optionName => $option) {
$r .= ' ' . $optionName . '="' . $option . '"';
        }

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>' . $input['value'] . '</textarea>';
    }

    static function getImageInput($input): string {
        $maxMemory   = Text::bytesAbbrToInt(ini_get('memory_limit'));
        $maxPostSize = Text::bytesAbbrToInt(ini_get('post_max_size'));
        $maxTotal    = min($maxMemory, $maxPostSize);

        $maxUploadSize  = min($maxTotal, Text::bytesAbbrToInt(ini_get('upload_max_filesize')));
        $maxUploadFiles = ini_get('max_file_uploads');

        $dataSizes = ' data-maxtotal="' . $maxTotal . '" data-maxsize="' . $maxUploadSize . '" data-maxcount="' . $maxUploadFiles . '"';

        $multiple = ($input['options']['multiple'] ?? false) ? ' multiple' : '';
        $disabled = ($maxUploadSize < 1 || $input['disabled']) ? ' disabled' : '';

        $typeInput = Input::PROTO_INPUT;
        if ($input['options']['type'] ?? []) {
            $typeInput = array_merge($typeInput, [
                'label'   => 'Type',
                'name'    => 'type',
                'type'    => 'radio',
                'value'   => array_key_first($input['options']['type']),
                'options' => $input['options']['type'],
            ]);
        }

        $existingInput = Input::PROTO_INPUT;
        if ($input['options']['existing'] ?? []) {
            $existingInput = array_merge($existingInput, [
                'label'   => 'Type',
                'name'    => 'type',
                'type'    => 'radio',
                'value'   => array_key_first($input['options']['existing']),
                'options' => $input['options']['existing'],
            ]);
        }


        $r = '';
$r .= '    <div>' . PHP_EOL;
$r .= '        <input type="hidden" name="MAX_FILE_SIZE" value="' . $maxUploadSize . '"' . $disabled . '>' . PHP_EOL;
$r .= '        <input name="' . $input['name'] . '" pattern="[a-z0-9\-]*" class="input-file input-image btn active" type="file" accept="image/jpeg,image/png"' . $dataSizes . $multiple . $disabled . '>' . PHP_EOL;
$r .= '        <div class="info">' . PHP_EOL;
$r .= '            <div>' . sprintf(Text::unsafet('Total size: <span class="maxtotal">0 B</span> / %s'), Text::bytesIntToAbbr($maxTotal)) . '</div>' . PHP_EOL;
$r .= '            <div>' . sprintf(Text::unsafet('Max file size: <span class="maxsize">0 B</span> / %s'), Text::bytesIntToAbbr($maxUploadSize)) . '</div>' . PHP_EOL;
$r .= '            <div>' . sprintf(Text::unsafet('Number of files: <span class="maxcount">0</span> / %s'), $maxUploadFiles) . '</div>' . PHP_EOL;
$r .= '        </div>' . PHP_EOL;
$r .= '    </div>' . PHP_EOL;

if ($input['options']['type'] ?? []) {
$r .= '    <div id="upload-images-type-proto" class="type control hide">' . PHP_EOL;
$r .= '        <div class="input-label">' . Text::t('Type') . '</div>' . PHP_EOL;
$r .= self::getRadioInput($typeInput) . PHP_EOL;
$r .= '    </div>' . PHP_EOL;
}

if ($input['options']['existing'] ?? []) {
$r .= '    <div id="upload-images-existing-proto" class="existing control hide">' . PHP_EOL;
$r .= '        <div class="input-label">' . Text::t('An image with the same slug already exists.') . '</div>' . PHP_EOL;
$r .= self::getRadioInput($existingInput) . PHP_EOL;
$r .= '    </div>' . PHP_EOL;
}

$r .= '    <div id="upload-images" class="list">' . PHP_EOL;
$r .= '    </div>' . PHP_EOL;

        return $r;
    }




    static function getSlugInput($input): string {
$r = '    <textarea pattern="[a-z0-9\-]*" class="input-text input-slug" name="' . $input['name'] . '" rows="1" wrap="soft"';

        foreach ($input['options'] as $optionName => $optionValue)
$r .= ' ' . $optionName . '="' . $optionValue . '"';

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>' . $input['value'] . '</textarea>';
    }




    static function getSlugImageInput($input): string {
$r = '    <textarea pattern="[a-z0-9\-]*" class="input-text input-slug input-slugImg" name="' . $input['name'] . '" rows="1" wrap="soft"';

        foreach ($input['options'] as $optionName => $optionValue)
$r .= ' ' . $optionName . '="' . $optionValue . '"';

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>' . $input['value'] . '</textarea>';
    }




    static function getRadioInput($input): string {
$r = '<div class="input-inputs">';
        foreach ($input['options'] as $optionValue => $option) {
            $css  = 'btn input-radio btn-pill';
            $attr = '';
            $icon = $option['icon'] ?? '';

            if (isset($option['cssClass'])) $css .= ' ' . $option['cssClass'];
            if ($optionValue == $input['value']) {
                $attr .= ' checked';
                $css  .= ' active';
            }
            if (isset($option['disabled'])) {
                $attr .= ' disabled';
                $css  .= ' disabled';
            }

$r .= '    <label class="' . Text::h($css) . '">' . PHP_EOL;
$r .= '        <input type="radio" name="' . $input['name'] . '" value="' . $optionValue . '"' . Text::h($attr) . '>' . PHP_EOL;
if ($icon) $r .= '        ' . Text::unsafe($icon) . PHP_EOL;
$r .= '        ' . Text::t($option['label']) . PHP_EOL;
$r .= '    </label>' . PHP_EOL;
        }
$r .= '</div>';

        return $r;
    }




    static function getSelectInput($input): string {
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
$r .= '        <option value="' . Text::h($optionValue) . '"' . $selected . '>' . Text::t($option['label'] ?? '') . '</option>' . PHP_EOL;
            $i++;
        }

$r .= '    </select>';

        return $r;
    }




    static function getTextareaInput($input): string {
$r = '    <textarea class="input-text input-textarea" name="' . $input['name'] . '" rows="1" wrap="soft"';

        foreach ($input['options'] as $optionName => $option) {
$r .= ' ' . $optionName . '="' . $option . '"';
        }

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>' . $input['value'] . '</textarea>';
    }




    static function getRicoInput($input): string {
$r = '    <input type="hidden" class="input-rico" name="' . $input['name'] . '" id="' . $input['name'] . '" value="' . Text::h($input['value']) . '"';

        if ($input['disabled']) $r .= ' disabled';

$r .= '>';

        if ($input['rico-new'] ?? []) {
            return $r . '    <rico-editor-new lang="' . $input['lang'] . '" input="' . $input['name'] . '"></rico-editor-new>';
        }

        return $r . '    <rico-editor lang="' . $input['lang'] . '" input="' . $input['name'] . '"></rico-editor>';
    }




    static function getTimestampInput($input): string {
        if (substr($input['value'], 16) == ':00') $input['value'] = substr($input['value'], 0, 16);
$r = '    <input class="input-text input-timestamp" type="text" name="' . $input['name'] . '" value="' . Text::h($input['value']) . '"';

        foreach ($input['options'] as $optionName => $option) {
$r .= ' ' . $optionName . '="' . $option . '"';
        }

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>';
    }




    static function getDateInput($input): string {
$r = '    <input class="input-text input-date" type="text" name="' . $input['name'] . '" value="' . Text::h($input['value']) . '"';

        foreach ($input['options'] as $optionName => $option) {
$r .= ' ' . $optionName . '="' . $option . '"';
        }

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>';
    }




    static function getTimeInput($input): string {
        if (substr($input['value'], 5) == ':00') $input['value'] = substr($input['value'], 0, 5);
$r = '    <input class="input-text input-time" type="text" name="' . $input['name'] . '" value="' . Text::h($input['value']) . '"';

        foreach ($input['options'] as $optionName => $option) {
$r .= ' ' . $optionName . '="' . $option . '"';
        }

        if ($input['disabled']) $r .= ' disabled';

        return $r . '>';
    }




    static function getImporterJsonldInput($input): string {
$r = '    <textarea class="input-text" rows="1" wrap="soft"></textarea>' . PHP_EOL;
$r .= '    <button type="button" class="btn btn-blue btn-pill rr scrape-jsonld">' . Text::t('Import') . '</button>' . PHP_EOL;
$r .= '    <script type="text/javascript">var importRelationsJsonld = ' . json_encode($input['options']) . '</script>' . PHP_EOL;

        return $r;
    }




    static function getImporterYoutubeInput($input): string {
$r = '    <textarea class="input-text" rows="1" wrap="soft">' . $input['value'] . '</textarea>' . PHP_EOL;
$r .= '    <button type="button" class="btn btn-blue btn-pill rr scrape-youtube">' . Text::t('Import') . '</button>' . PHP_EOL;
$r .= '    <script type="text/javascript">var importRelationsYoutube = ' . json_encode($input['options']) . '</script>' . PHP_EOL;

        return $r;
    }




    static function getImporterVimeoInput($input): string {
$r = '    <textarea class="input-text" rows="1" wrap="soft">' . $input['value'] . '</textarea>' . PHP_EOL;
$r .= '    <button type="button" class="btn btn-blue btn-pill rr scrape-vimeo">' . Text::t('Import') . '</button>' . PHP_EOL;
$r .= '    <script type="text/javascript">var importRelationsVimeo = ' . json_encode($input['options']) . '</script>' . PHP_EOL;

        return $r;
    }




}
