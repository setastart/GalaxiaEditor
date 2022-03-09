<?php

use Galaxia\AppImage;
use Galaxia\ArrayShape;
use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


G::$editor->view = 'image/image';
$mtime        = filemtime(G::dirImage() . E::$imgSlug . '/');




// item validation

foreach (E::$imgInputs as $name => $input) {
    if (!isset($_POST[$name])) continue;
    $value = $_POST[$name];
    $input = Input::validate($input, $value);

    if ($name == 'imgSlug' && $value != E::$imgSlug) {
        if ($value && is_dir(G::dirImage() . $value)) {
            $msg               = 'Must be unique. An item with that value already exists.';
            $input['errors'][] = $msg;
            Flash::error($msg, 'form', $input['name']);
            if ($input['lang']) E::$langSelectClass[$input['lang']] = 'btn-red';
        }
    }

    if ($input['value'] !== $input['valueFromDb'])
        E::$imgChanges[$name] = $input['valueToDb'];

    E::$imgInputs[$name] = $input;
}




// finish validation

if (Flash::hasError()) return;
if (!E::$imgChanges) {
    Flash::warning(Text::t('No changes were made.'));
    if (isset($_POST['submitAndGoBack'])) G::redirect('edit/' . E::$pgSlug);

    return;
}




// update alt and type

$altsAndType = ['alt_', 'type'];
ArrayShape::languify($altsAndType, array_keys(G::locales()));
foreach (E::$imgChanges as $name => $value) {

    if (!in_array($name, $altsAndType)) continue;

    $file = G::dirImage() . E::$imgSlug . '/' . E::$imgSlug . '_' . $name . '.txt';

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

touch(G::dirImage() . E::$imgSlug . '/', $mtime);
foreach (E::$imgChanges as $name => $value) {
    if ($name != 'timestampM') continue;
    if (touch(G::dirImage() . E::$imgSlug . '/', strtotime($value))) {
        Flash::info(Text::t('Updated') . ': ' . $name);
        Flash::info(Text::t('Updated'), 'form', $name);
    } else {
        Flash::info(Text::t('Not updated') . ': ' . $name);
        Flash::info(Text::t('Not updated'), 'form', $name);
    }
}




// rename

if (isset(E::$imgChanges['imgSlug'])) {
    if (AppImage::slugRename(G::dirImage(), E::$imgSlug, E::$imgChanges['imgSlug'])) {
        Flash::info(Text::t('Updated') . ': ' . 'Slug');
        Flash::info(Text::t('Updated'), 'form', 'imgSlug');

        foreach (E::$section['gcImagesInUse'] as $table => $inUse) {
            $imgSlugCol = $inUse['gcSelect'][$table][0];
            $slugChange = [$imgSlugCol => E::$imgChanges['imgSlug']];
            $params     = array_values($slugChange);
            $params[]   = E::$imgSlug;

            $query = Sql::update([$table => [$imgSlugCol]]);
            $query .= Sql::updateSet(array_keys($slugChange));
            $query .= Sql::updateWhere([$table => [$imgSlugCol]]);

            $affectedRows = 0;
            try {
                $stmt  = G::prepare($query);
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
        E::$imgSlug = E::$imgChanges['imgSlug'];
    } else {
        Flash::error('image-update - Unable to update image slug: ' . E::$imgSlug);
    }
}




// finish

G::cacheDelete(['app', 'fastroute']);
G::cacheDelete('editor', 'imageList-' . E::$pgSlug . '*');

if (isset($_POST['submitAndGoBack'])) G::redirect('edit/' . E::$pgSlug);
G::redirect('edit/' . E::$pgSlug . '/' . E::$imgSlug);
