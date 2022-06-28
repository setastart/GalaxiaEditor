<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\G;
use Galaxia\Session;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


G::$editor->view = 'login/login';


E::$loginInputs['userEmail']    = Input::validate(E::$loginInputs['userEmail'], $_POST['userEmail']);
E::$loginInputs['userPassword'] = Input::validate(E::$loginInputs['userPassword'], $_POST['userPassword']);


if (!empty(E::$loginInputs['userEmail']['errors'])) {
    return;
}


if (!E::$auth->userAuthenticateEmailPassword(E::$loginInputs['userEmail']['value'], E::$loginInputs['userPassword']['value'])) {
    E::$loginInputs['userPassword']['errors'][] = 'Wrong password.';

    return;
}




// user is logged in. Reload.
$userId = E::$auth->userGetIdByEmail(E::$loginInputs['userEmail']['value']);
if ($userId) {
    if (session_status() != PHP_SESSION_ACTIVE) {
        session_name(G::$app->cookieEditorKey); // name of the cookie that holds the session id
        session_set_cookie_params([
            'lifetime' => 31536000,
            'path'     => '/',
            'secure'   => G::$req->isHttps(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_set_save_handler(new Session('_geUser'), true);
        session_register_shutdown();
        session_start();
    }
    $_SESSION['id'] = $userId;

    G::redirect('edit', 303);
}
G::redirect('edit', 303);
