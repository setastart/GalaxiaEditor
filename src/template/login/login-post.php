<?php

use Galaxia\{Session};
use GalaxiaEditor\input\Input;


$editor->view = 'login/login';


$inputs['userEmail']    = Input::validateInput($inputs['userEmail'], $_POST['userEmail']);
$inputs['userPassword'] = Input::validateInput($inputs['userPassword'], $_POST['userPassword']);


if (!empty($inputs['userEmail']['errors'])) {
    return;
}


if (!$auth->userAuthenticateEmailPassword($inputs['userEmail']['value'], $inputs['userPassword']['value'])) {
    $inputs['userPassword']['errors'][] = 'Wrong password.';

    return;
}




// user is logged in. Reload.
$userId = $auth->userGetIdByEmail($inputs['userEmail']['value']);
if ($userId) {
    if (session_status() != PHP_SESSION_ACTIVE) {
        session_name($app->cookieEditorKey); // name of the cookie that holds the session id
        if (ip2long($_SERVER['HTTP_HOST'])) {
            session_set_cookie_params(
                31536000,
                '/; SameSite=Strict',
                $_SERVER['HTTP_HOST'],
                false,
                true
            ); // 31536000 seconds = 1 year
        } else {
            session_set_cookie_params(
                31536000,
                '/; SameSite=Strict',
                '.' . $_SERVER['SERVER_NAME'],
                isset($_SERVER['HTTPS']),
                true
            ); // 31536000 seconds = 1 year
        }
        session_set_save_handler(new Session('_geUser'), true);
        session_register_shutdown();
        session_start();
    }
    $_SESSION['id'] = $userId;

    redirect('edit', 303);
}
redirect('edit', 303);
