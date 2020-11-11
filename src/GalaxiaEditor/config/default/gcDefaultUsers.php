<?php

return [

    'gcPageType'    => 'gcpListItem',
    'gcMenuTitle'   => 'Users',
    'gcTitleSingle' => 'User',
    'gcTitlePlural' => 'Users',
    'gcMenuShow'    => ['gcPerms' => ['dev']],

    'gcColNames' => [
        '_geUserId'           => 'Id',
        'email'               => 'Email',
        'name'                => 'Name',
        'perms'               => 'Permissions',
        'timestampCreated'    => 'Created',
        'timestampLastOnline' => 'Last Online',
        'passwordCurrent'     => 'Your Password',
        'passwordHash'        => 'New Password',
        'passwordRepeat'      => 'Repeat Password',
    ],

    'gcList' => [
        'gcPerms' => ['dev'],

        'gcSelect'        => ['_geUser' => ['_geUserId', 'email', 'name', 'perms', 'timestampLastOnline']],
        'gcSelectLJoin'   => [],
        'gcSelectOrderBy' => ['_geUser' => ['name' => 'ASC']],

        'gcLinks' => [
            'new' => [
                'label'    => '+ Add new User',
                'cssClass' => 'btn-blue active',
            ],
        ],

        'gcColumns' => [
            [
                'label'        => 'Name and Email',
                'cssClass'     => 'flex3',
                'gcColContent' => [['dbTab' => '_geUser', 'dbCols' => ['name', 'email'], 'colType' => 'text']],
            ],
            [
                'label'        => 'Permissions',
                'cssClass'     => 'flex2',
                'gcColContent' => [['dbTab' => '_geUser', 'dbCols' => ['perms'], 'colType' => 'text']],
            ],
            [
                'label'        => 'Last Online',
                'cssClass'     => 'flexD',
                'gcColContent' => [['dbTab' => '_geUser', 'dbCols' => ['timestampLastOnline'], 'colType' => 'timestamp']],
            ],
        ],

        'gcFilterTexts' => [
            [
                'label'      => 'Filter Names and Emails',
                'filterWhat' => ['_geUser' => ['name', 'email']],
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

        'gcInsert'      => ['_geUser' => ['email', 'name', 'perms'], 'gcPerms' => ['dev']],
        'gcSelect'      => ['_geUser' => ['_geUserId', 'email', 'name', 'perms', 'timestampCreated', 'timestampLastOnline']],
        'gcSelectLJoin' => [],
        'gcSelectExtra' => [],
        'gcUpdate'      => ['_geUser' => ['_geUserId', 'email', 'name', 'perms']],
        'gcDelete'      => ['_geUser' => ['_geUserId'], 'gcPerms' => ['dev']],

        'gcInputs' => [
            'name'  => ['type' => 'text', 'options' => ['maxlength' => 255]],
            'email' => ['type' => 'email', 'gcPerms' => ['dev']],
            'perms' => [
                'gcPerms'  => ['dev'],
                'type'     => 'text',
                'options'  => ['maxlength' => 255],
                'nullable' => true,
            ],
        ],

        'gcInputsWhere' => [],

        'gcModules' => [
            [
                'gcTable'               => '_geUserOption',
                'gcModuleType'          => 'fields',
                'gcModuleTitle'         => 'Options',
                'gcModuleShowUnused'    => ['gcPerms' => ['dev']],
                'gcModuleDeleteIfEmpty' => ['value'],
                'gcModuleMultiple'      => [],

                'gcSelect'        => ['_geUserOption' => ['_geUserOptionId', 'fieldKey', '_geUserId', 'value']],
                'gcSelectLJoin'   => [],
                'gcSelectOrderBy' => ['_geUserOption' => ['fieldKey' => 'ASC']],
                'gcSelectExtra'   => [],
                'gcUpdate'        => ['_geUserOption' => ['value']],

                'gcInputs' => ['value' => ['type' => 'textarea', 'nullable' => true]],

                'gcInputsWhereCol' => [
                    'Language' => [
                        'value' => [
                            'type'    => 'select',
                            'label'   => 'Language',
                            'options' => [
                                'en' => ['label' => 'English'],
                                'pt' => ['label' => 'Português'],
                                'es' => ['label' => 'Español'],
                            ],
                        ],
                    ],

                    'menuPosition' => [
                        'value' => [
                            'type'    => 'select',
                            'label'   => 'Menu position',
                            'options' => [
                                ''      => ['label' => 'Left'],
                                'right' => ['label' => 'Right'],
                            ],
                        ],
                    ],
                ],

                'gcInputsWhereParent' => [],
            ],
        ],

        'gcInfo' => [
            '_geUserId'           => ['type' => 'text'],
            'timestampCreated'    => ['type' => 'timestamp'],
            'timestampLastOnline' => ['type' => 'timestamp', 'nullable' => true],
        ],
    ],

];
