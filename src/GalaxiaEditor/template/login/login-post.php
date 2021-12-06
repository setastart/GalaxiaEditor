<?php

use Galaxia\G;
use Galaxia\Session;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


G::$editor->view = 'login/login';


$inputs['userEmail']    = Input::validate($inputs['userEmail'], $_POST['userEmail']);
$inputs['userPassword'] = Input::validate($inputs['userPassword'], $_POST['userPassword']);


if (!empty($inputs['userEmail']['errors'])) {
    return;
}


if (!E::$auth->userAuthenticateEmailPassword($inputs['userEmail']['value'], $inputs['userPassword']['value'])) {
    $inputs['userPassword']['errors'][] = 'Wrong password.';

    return;
}




// user is logged in. Reload.
$userId = E::$auth->userGetIdByEmail($inputs['userEmail']['value']);
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
