<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

return [

    'gcPageType'    => 'gcpListItem',
    'gcMenuTitle'   => 'Passwords',
    'gcTitleSingle' => 'Password',
    'gcTitlePlural' => 'Passwords',
    'gcMenuShow'    => ['gcPerms' => ['dev']],

    'gcColNames' => [
        '_geUserId'           => 'Id',
        'email'               => 'Email',
        'name'                => 'Type',
        'perms'               => 'Permissions',
        'timestampCreated'    => 'Created',
        'timestampLastOnline' => 'Last Online',
        'passwordCurrent'     => 'Your Password',
        'passwordHash'        => 'New Password',
        'passwordRepeat'      => 'Repeat Password',
    ],

    'gcList' => [
        'gcPerms'  => ['dev'],

        'gcSelect'        => ['_geUser' => ['_geUserId', 'email', 'name', 'perms', 'timestampLastOnline']],
        'gcSelectLJoin'   => [],
        'gcSelectOrderBy' => ['_geUser' => ['name' => 'ASC']],

        'gcLinks' => [],
        'gcColumns' => [
            [
                'label' => 'Name and Email',
                'cssClass' => 'flex3',
                'gcColContent' => [['dbTab' => '_geUser', 'dbCols' => ['name', 'email'], 'colType' => 'text']],
            ],
            [
                'label' => 'Permissions',
                'cssClass' => 'flex2',
                'gcColContent' => [['dbTab' => '_geUser', 'dbCols' => ['perms'], 'colType' => 'text']],
            ],
            [
                'label' => 'Last Online',
                'cssClass' => 'flexD',
                'gcColContent' => [['dbTab' => '_geUser', 'dbCols' => ['timestampLastOnline'], 'colType' => 'timestamp']],
            ],
        ],
        'gcFilterTexts' => [
            [
                'label'       => 'Filter Names and Emails',
                'filterWhat'  => ['_geUser' => ['name', 'email']],
            ],
            [
                'label'       => 'Filter Permissions',
                'filterWhat'  => ['_geUser' => ['perms']],
                'filterEmpty' => true,
            ],
        ],
        'gcFilterInts' => [],
    ],

    'gcItem' => [
        'gcTable'         => '_geUser',
        'gcColKey'        => 'name',
        'gcVisit'         => false,
        'gcUpdateOnlyOwn' => true,
        'gcRedirect'      => false,

        'gcInsert'      => [],
        'gcSelect'      => ['_geUser' => ['_geUserId', 'passwordHash', 'email', 'name', 'perms', 'timestampCreated', 'timestampLastOnline']],
        'gcSelectLJoin' => [],
        'gcSelectExtra' => [],
        'gcUpdate'      => ['_geUser' => ['_geUserId', 'passwordHash']],
        'gcDelete'      => [],

        'gcInputs' => [
            'passwordCurrent' => ['type' => 'password', 'options' => ['maxlength' => 255]],
            'passwordHash'    => ['type' => 'password', 'options' => ['maxlength' => 255]],
            'passwordRepeat'  => ['type' => 'password', 'options' => ['maxlength' => 255]],
        ],
        'gcInputsWhere' => [],

        'gcModules' => [],

        'gcInfo' => [
            '_geUserId'           => ['type' => 'text'],
            'timestampCreated'    => ['type' => 'timestamp'],
            'timestampLastOnline' => ['type' => 'timestamp', 'nullable' => true],
        ],
    ],

];
