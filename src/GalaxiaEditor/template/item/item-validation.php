<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


if (!isset($_POST['item'])) $_POST['item'] = [];



// password field changes require inputting current password

if (E::$passwordColsFound) {
    if (!isset($_POST['item']['passwordCurrent'])) Flash::error(Text::t('Your current password is required.'));
    if (!isset($_POST['item']['passwordHash']))    Flash::error(Text::t('Your new password is required.'));
    if (!isset($_POST['item']['passwordRepeat']))  Flash::error(Text::t('Your new password repeat is required.'));

    if (!isset(E::$item['inputs']['passwordCurrent'])) Flash::error(Text::t('Your current password is required - input.'));
    if (!isset(E::$item['inputs']['passwordHash']))    Flash::error(Text::t('Your new password is required - input.'));
    if (!isset(E::$item['inputs']['passwordRepeat']))  Flash::error(Text::t('Your new password repeat is required - input.'));

    if (!E::$auth->userAuthenticateIdPassword(G::$me->id, $_POST['item']['passwordCurrent'])) {
        E::$item['inputs']['passwordCurrent']['errors'][] = 'Wrong current password.';
        return false;
    }

    if (E::$item['inputs']['passwordHash'] != isset(E::$item['inputs']['passwordRepeat'])) {
        E::$item['inputs']['passwordRepeat']['errors'][] = 'Passwords don\'t match.';
        return false;
    }
}


if (G::isDev() && G::$me->id == E::$itemId) {
    if (isset($_POST['item']['perms']) && isset(E::$item['inputs']['perms'])) {
        $explodedPerms = explode(',', $_POST['item']['perms']);
        if (!in_array('dev', $explodedPerms)) E::$item['inputs']['perms']['errors'][] = 'Cannot lose own dev permission';
    }
}



// validate inputs

foreach ($_POST['item'] as $name => $value) {
    if (!isset(E::$item['inputs'][$name])) continue;
    $input = Input::validate(E::$item['inputs'][$name], $value, E::$itemId);

    if ($input['dbUnique']) {

        $query = Sql::selectFirst(E::$item['gcUpdate']);
        $query .= Sql::selectWhere([key(E::$item['gcUpdate']) => [$input['nameFromDb'] => '=']]);
        $query .= Sql::selectLimit(0, 1);

        $stmt = G::prepare($query);
        $stmt->bind_param('s', $input['value']);
        $stmt->bind_result($rowId);
        $stmt->execute();
        $stmt->fetch();
        $stmt->close();

        if ($rowId && (string)$rowId != E::$itemId) {
            $input['errors'][] = 'Must be unique. An item with that value already exists.';
        }

    }

    foreach ($input['errors'] as $msg) {
        Flash::error($msg, 'form', $input['name']);
        if ($input['lang']) {
            E::$langSelectClass[$input['lang']] = 'btn-red';
        }
    }

    if ($input['value'] !== $input['valueFromDb']) {
        E::$itemChanges[$input['nameFromDb']] = $input['valueToDb'];
    }

    E::$item['inputs'][$name] = $input;
}
