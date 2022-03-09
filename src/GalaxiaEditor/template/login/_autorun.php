<?php

use Galaxia\G;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


E::$pgTitle = 'Editor Login';
E::$hdTitle = 'Editor for ' . G::$req->host;

E::$loginInputs = [];

E::$loginInputs['userEmail'] = array_merge(Input::PROTO_INPUT, [
    'label'    => 'Email',
    'name'     => 'userEmail',
    'type'     => 'email',
]);

E::$loginInputs['userPassword'] = array_merge(Input::PROTO_INPUT, [
    'label'    => 'Password',
    'name'     => 'userPassword',
    'type'     => 'password',
    'options'  => ['minlength' => 8]
]);
