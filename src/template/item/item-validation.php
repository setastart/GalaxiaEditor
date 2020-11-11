<?php


use Galaxia\Flash;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\input\Input;


if (!isset($_POST['item'])) $_POST['item'] = [];



// password field changes require inputting current password

if ($passwordColsFound) {
    if (!isset($_POST['item']['passwordCurrent'])) Flash::error(Text::t('Your current password is required.'));
    if (!isset($_POST['item']['passwordHash']))    Flash::error(Text::t('Your new password is required.'));
    if (!isset($_POST['item']['passwordRepeat']))  Flash::error(Text::t('Your new password repeat is required.'));

    if (!isset($item['inputs']['passwordCurrent'])) Flash::error(Text::t('Your current password is required - input.'));
    if (!isset($item['inputs']['passwordHash']))    Flash::error(Text::t('Your new password is required - input.'));
    if (!isset($item['inputs']['passwordRepeat']))  Flash::error(Text::t('Your new password repeat is required - input.'));

    if (!$auth->userAuthenticateIdPassword($me->id, $_POST['item']['passwordCurrent'])) {
        $item['inputs']['passwordCurrent']['errors'][] = 'Wrong current password.';
        return false;
    }

    if ($item['inputs']['passwordHash'] != isset($item['inputs']['passwordRepeat'])) {
        $item['inputs']['passwordRepeat']['errors'][] = 'Passwords don\'t match.';
        return false;
    }
}


if ($me->hasPerm('dev') && $me->id == $itemId) {
    if (isset($_POST['item']['perms']) && isset($item['inputs']['perms'])) {
        $explodedPerms = explode(',', $_POST['item']['perms']);
        if (!in_array('dev', $explodedPerms)) $item['inputs']['perms']['errors'][] = 'Cannot lose own dev permission';
    }
}



// validate inputs

foreach ($_POST['item'] as $name => $value) {
    if (!isset($item['inputs'][$name])) continue;
    $input = Input::validateInput($item['inputs'][$name], $value);

    if ($input['dbUnique']) {

        $query = Sql::selectFirst($item['gcUpdate']);
        $query .= Sql::selectWhere([key($item['gcUpdate']) => [$input['nameFromDb'] => '=']]);
        $query .= Sql::selectLimit(0, 1);

        $stmt = $db->prepare($query);
        $stmt->bind_param('s', $input['value']);
        $stmt->bind_result($rowId);
        $stmt->execute();
        $stmt->fetch();
        $stmt->close();

        if ($rowId && (string)$rowId != $itemId) {
            $input['errors'][] = 'Must be unique. An item with that value already exists.';
        }

    }

    foreach ($input['errors'] as $msg) {
        Flash::error($msg, 'form', $input['name']);
        if ($input['lang']) {
            $langSelectClass[$input['lang']] = 'btn-red';
        }
    }

    if ($input['value'] !== $input['valueFromDb']) {
        $itemChanges[$input['nameFromDb']] = $input['valueToDb'];
    }

    $item['inputs'][$name] = $input;
}
