<?php

$pgTitle = 'Editor Login';
$hdTitle = 'Editor for ' . $_SERVER['SERVER_NAME'];

$inputs = [];

$inputs['userEmail'] = array_merge(PROTO_INPUT, [
    'label'    => 'Email',
    'name'     => 'userEmail',
    'type'     => 'email',
]);

$inputs['userPassword'] = array_merge(PROTO_INPUT, [
    'label'    => 'Password',
    'name'     => 'userPassword',
    'type'     => 'password',
    'options'  => ['minlength' => 8]
]);
