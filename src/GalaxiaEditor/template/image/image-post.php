<?php

use Galaxia\AppImage;
use Galaxia\ArrayShape;
use Galaxia\Director;
use Galaxia\Flash;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\input\Input;


$editor->view = 'image/image';
$mtime        = filemtime($app->dirImage . $imgSlug . '/');




// item validation

foreach ($inputs as $name => $input) {
    if (!isset($_POST[$name])) continue;
    $value = $_POST[$name];
    $input = Input::validateInput($input, $value);

    if ($name == 'imgSlug' && $value != $imgSlug) {
        if ($value && is_dir($app->dirImage . $value)) {
            $msg               = 'Must be unique. An item with that value already exists.';
            $input['errors'][] = $msg;
            Flash::error($msg, 'form', $input['name']);
            if ($input['lang']) $langSelectClass[$input['lang']] = 'btn-red';
        }
    }

    if ($input['value'] !== $input['valueFromDb'])
        $itemChanges[$name] = $input['valueToDb'];

    $inputs[$name] = $input;
}




// finish validation

if (Flash::hasError()) return;
if (!$itemChanges) {
    Flash::warning(Text::t('No changes were made.'));
    if (isset($_POST['submitAndGoBack'])) Director::redirect('edit/' . $pgSlug);

    return;
}




// update alt and type

$altsAndType = ['alt_', 'type'];
ArrayShape::languify($altsAndType, array_keys($app->locales));
foreach ($itemChanges as $name => $value) {

    if (!in_array($name, $altsAndType)) continue;

    $file = $app->dirImage . $imgSlug . '/' . $imgSlug . '_' . $name . '.txt';

    if (empty($value)) {
        unlink($file);
        continue;
    }

    $fp = fopen($file, 'w');
    if (fwrite($fp, $value)) {
        Flash::info(Text::t('Updated') . ': ' . $name);
        Flash::info(Text::t('Updated'), 'form', $name);
    } else {
        Flash::info(Text::t('Not updated') . ': ' . $name);
        Flash::info(Text::t('Not updated'), 'form', $name);
    }
    fclose($fp);
}




// update mtime

touch($app->dirImage . $imgSlug . '/', $mtime);
foreach ($itemChanges as $name => $value) {
    if ($name != 'timestampM') continue;
    if (touch($app->dirImage . $imgSlug . '/', strtotime($value))) {
        Flash::info(Text::t('Updated') . ': ' . $name);
        Flash::info(Text::t('Updated'), 'form', $name);
    } else {
        Flash::info(Text::t('Not updated') . ': ' . $name);
        Flash::info(Text::t('Not updated'), 'form', $name);
    }
}




// rename

if (isset($itemChanges['imgSlug'])) {
    if (AppImage::slugRename($app->dirImage, $imgSlug, $itemChanges['imgSlug'])) {
        Flash::info(Text::t('Updated') . ': ' . 'Slug');
        Flash::info(Text::t('Updated'), 'form', 'imgSlug');

        foreach ($geConf[$pgSlug]['gcImagesInUse'] as $table => $inUse) {
            $imgSlugCol = $inUse['gcSelect'][$table][0];
            $slugChange = [$imgSlugCol => $itemChanges['imgSlug']];
            $params     = array_values($slugChange);
            $params[]   = $imgSlug;

            $query = Sql::update([$table => [$imgSlugCol]]);
            $query .= Sql::updateSet(array_keys($slugChange));
            $query .= Sql::updateWhere([$table => [$imgSlugCol]]);

            $affectedRows = 0;
            try {
                $stmt  = $db->prepare($query);
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $affectedRows = $stmt->affected_rows;
                $stmt->close();
            } catch (Exception $e) {
                Flash::error('image-update - Unable to rename database image slug in: ' . Text::h($table));
                Flash::error($e->getMessage());
                continue;
            }
            if ($affectedRows)
                Flash::info(Text::h($table) . ': Renamed slugs: ' . Text::h($affectedRows));
        }
        $imgSlug = $itemChanges['imgSlug'];
    } else {
        Flash::error('image-update - Unable to update image slug: ' . $imgSlug);
    }
}




// finish

$app->cacheDelete(['app', 'fastroute']);
$app->cacheDelete('editor', 'imageList-' . $pgSlug);

if (isset($_POST['submitAndGoBack'])) Director::redirect('edit/' . $pgSlug);
Director::redirect('edit/' . $pgSlug . '/' . $imgSlug);
