<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

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
