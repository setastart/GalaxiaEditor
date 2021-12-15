<?php


namespace GalaxiaEditor\config;


use Galaxia\Flash;
use GalaxiaEditor\E;
use GalaxiaEditor\input\Input;


class Config {

    // GalaxiaEditor config proto array
    // defines an array schema the website config must follow
    // gcExample:  galaxia config
    // gcpExample: galaxia config proto
    public const PROTO_GC = [
        'gcpSeparator' => [
            'gcPageType' => 'string',
        ],

        'gcpHooks' => [
            '?gcHookTranslate' => 'string',
        ],

        'gcpListItem' => [
            'gcPageType'    => 'string',
            'gcMenuTitle'   => 'string',
            'gcTitleSingle' => 'string',
            'gcTitlePlural' => 'string',
            'gcMenuShow'    => 'boolean',

            'gcColNames' => 'stringArray',

            'gcList' => [
                'gcSelect'        => 'tableWithCols',
                'gcSelectLJoin'   => 'tableWithCols',
                'gcSelectOrderBy' => 'tableWithColsOrder',

                'gcLinks'       => 'gcpLinks',
                'gcColumns'     => 'gcpColumns',
                'gcFilterTexts' => 'gcpFilterTexts',
                'gcFilterInts'  => 'gcpFilterInts',
            ],

            'gcItem' => [
                'gcTable'          => 'string',
                'gcColKey'         => 'string',
                'gcVisit'          => 'gcpVisit',
                '?gcUpdateOnlyOwn' => 'boolean',
                'gcRedirect'       => 'boolean',

                'gcInsert'      => 'tableWithCols',
                'gcSelect'      => 'tableWithCols',
                'gcSelectLJoin' => 'tableWithCols',
                'gcSelectExtra' => 'tableWithCols',
                'gcUpdate'      => 'tableWithCols',
                'gcDelete'      => 'tableWithCols',

                'gcInputs'      => 'inputs',
                'gcInputsWhere' => 'inputsWhere',

                'gcModules' => 'gcpModules',

                'gcInfo' => 'inputs',
            ],
        ],

        'gcpHistory' => [
            'gcPageType'    => 'string',
            'gcMenuTitle'   => 'string',
            'gcTitleSingle' => 'string',
            'gcTitlePlural' => 'string',
            'gcMenuShow'    => 'boolean',
        ],

        'gcpChat' => [
            'gcPageType'  => 'string',
            'gcMenuTitle' => 'string',
            'gcMenuShow'  => 'boolean',
        ],

        'gcpImages' => [
            'gcPageType'    => 'string',
            'gcMenuTitle'   => 'string',
            'gcTitleSingle' => 'string',
            'gcTitlePlural' => 'string',
            'gcMenuShow'    => 'boolean',
            'gcImageTypes'  => 'intArray',
            'gcImagesInUse' => 'gcpImagesInUse',

            'gcImageList' => [
                'gcLinks' => 'gcpLinks',
            ],

            'gcImage' => [
                'gcSelect' => 'tableWithCols',
                'gcInsert' => 'tableWithCols',
                'gcUpdate' => 'tableWithCols',
                'gcDelete' => 'tableWithCols',
            ],
        ],

        'gcpImagesInUse' => [
            'gcSelect'        => 'tableWithCols',
            'gcSelectLJoin'   => 'tableWithCols',
            'gcSelectOrderBy' => 'tableWithColsOrder',
        ],

        'gcpLinkToItem' => [
            'gcPageType'    => 'string',
            'gcMenuTitle'   => 'string',
            'gcMenuShow'    => 'boolean',
            '?geLinkToUser' => 'string',
            '?geLinkToItem' => 'stringArray',
        ],

        'gcpGoaccessStats' => [
            'gcPageType'    => 'string',
            'gcMenuTitle'   => 'string',
            'gcMenuShow'    => 'boolean',
            '?gcGoaccessLog' => 'string',
            '?gcGoaccessDir' => 'string',
        ],

        'gcpLinks' => [
            '?label'           => 'string',
            '?cssClass'        => 'string',
            '?gcSelectOrderBy' => 'tableWithColsOrder',
        ],

        'gcpColumns' => [
            '?label'       => 'string',
            'cssClass'     => 'string',
            'gcColContent' => 'gcpRowData',
        ],

        'gcpRowData' => [
            'dbTab'     => 'string',
            'dbCols'    => 'stringArray',
            'colType'   => 'string',
            '?gcParent' => 'stringArray',
        ],

        'gcpFilterTexts' => [
            'label'        => 'string',
            'filterWhat'   => 'tableWithCols',
            '?filterEmpty' => 'boolean',
        ],

        'gcpFilterInts' => [
            'label'       => 'string',
            'filterWhat'  => 'tableWithCols',
            'options'     => 'options',
            '?filterType' => 'string',
        ],

        'gcpModules' => [
            'gcTable'               => 'string',
            'gcModuleType'          => 'string',
            'gcModuleTitle'         => 'string',
            'gcModuleShowUnused'    => 'boolean',
            'gcModuleDeleteIfEmpty' => 'stringArray',
            'gcModuleMultiple'      => 'moduleMultiple',

            'gcSelect'        => 'tableWithCols',
            'gcSelectLJoin'   => 'tableWithCols',
            'gcSelectOrderBy' => 'tableWithColsOrder',
            'gcSelectExtra'   => 'tableWithCols',
            'gcUpdate'        => 'tableWithCols',

            '?gcFieldOrder' => 'stringArray',

            'gcInputs'            => 'inputs',
            'gcInputsWhereCol'    => 'inputsWhereCol',
            'gcInputsWhereParent' => 'inputsWhereParent',
        ],

        'gcpModuleMultiple' => [
            'reorder'  => 'boolean',
            'unique'   => 'stringArray',
            '?label'   => 'string',
            '?gallery' => 'boolean',
        ],
    ];




    static function validate() {
        foreach (E::$conf as $key => $confPage) {
            if (!isset($confPage['gcPageType']))
                Config::geConfigParseError($key . '/gcPageType missing.');

            if (!in_array($confPage['gcPageType'], ['gcpListItem', 'gcpHistory', 'gcpChat', 'gcpImages', 'gcpLinkToItem', 'gcpGoaccessStats', 'gcpSeparator', 'gcpHooks']))
                Config::geConfigParseError($key . '/gcPageType missing.');

            if ($confPage['gcPageType'] == 'gcpHooks') {
                E::$hookTranslate = $confPage['gcHookTranslate'] ?? '';
            }

            Config::geConfigParse($key, Config::PROTO_GC[$confPage['gcPageType']], $confPage, '');
        }
    }




    private static function geConfigParse($schemaKey, $schema, $config, $errorString) {
        $errorString .= $schemaKey . '/';

        foreach ($schema as $key => $val) {
            if (substr($key, 0, 1) == '?') {
                $key = substr($key, 1);
                if (!isset($config[$key])) continue;
            }

            if (!isset($config[$key])) {
                Config::geConfigParseError($errorString . $key . ' missing.');
            }

            if (is_string($val)) { // is terminal
                Config::geConfigParseFine($val, $config[$key], $errorString . $key);
                continue;
            }

            if (empty($config[$key])) continue;

            if (is_array($val)) {
                Config::geConfigParse($key, $schema[$key], $config[$key], $errorString);
                continue;
            }

            Config::geConfigParseError($errorString . ' - should not reach this: ' . $key, $schema, $config);
        }

        $extraKeys = array_diff_key($config, $schema);
        foreach ($extraKeys as $key => $val) {
            if ($key == 'gcPerms') continue;
            if (substr($key, 0, 1) == '?') continue;
            if (isset($schema['?' . $key])) continue;

            Flash::devlog($errorString . ' - extra keys: ' . $key);
        }
    }




    private static function geConfigParseFine($schema, $config, $errorString) {
        switch ($schema) {

            case 'boolean':
                if (is_bool($config)) break;
                if (is_array($config) && count($config) == 1 && isset($config['gcPerms'])) break;
                Config::geConfigParseError($errorString . ' should be "true" or "gePerms".', $schema, $config);
                break;


            case 'int':
                if (!is_bool($config) && !is_int($config))
                    Config::geConfigParseError($errorString . ' should be an integer.', $schema, $config);
                break;


            case 'string':
                if (!is_string($config))
                    Config::geConfigParseError($errorString . ' should be a string.', $schema, $config);
                break;


            case 'intArray':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array of ints.', $schema, $config);

                foreach ($config as $key => $val)
                    if (!is_int($val))
                        Config::geConfigParseError($errorString . '/' . $key . ' should be an int.', $schema, $config);
                break;


            case 'stringArray':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array of strings.', $schema, $config);

                foreach ($config as $key => $val)
                    if (!is_string($val))
                        Config::geConfigParseError($errorString . '/' . $key . ' should be a string.', $schema, $config);
                break;


            case 'tableWithCols':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array.', $schema, $config);

                foreach ($config as $key => $val) {
                    if (is_array($config[$key]) && count($config[$key]) == 1 && isset($config[$key]['gcPerms'])) continue;

                    if (!is_array($val))
                        Config::geConfigParseError($errorString . '/' . $key . ' should be an array.', $schema, $config);

                    foreach ($config[$key] as $key2 => $val2)
                        if (!is_string($val2))
                            Config::geConfigParseError($errorString . '/' . $key . '/' . $key2 . ' should be a string.', $schema, $config);
                }
                break;


            case 'tableWithColsOrder':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array.', $schema, $config);

                foreach ($config as $key => $val) {
                    if (!is_array($val))
                        Config::geConfigParseError($errorString . '/' . $key . ' should be an array.', $schema, $config);

                    foreach ($config[$key] as $key2 => $val2)
                        if (!is_string($val2) || !in_array($val2, ['ASC', 'DESC']))
                            Config::geConfigParseError($errorString . '/' . $key . '/' . $key2 . ' should be ASC or DESC.', $schema, $config);
                }
                break;


            case 'gcpLinks':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array.', $schema, $config);

                foreach ($config as $key => $val)
                    Config::geConfigParse('/' . $key, Config::PROTO_GC['gcpLinks'], $config[$key], $errorString);
                break;


            case 'gcpColumns':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array.', $schema, $config);

                foreach ($config as $key => $val)
                    Config::geConfigParse('/' . $key, Config::PROTO_GC['gcpColumns'], $config[$key], $errorString);
                break;


            case 'gcpRowData':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array.', $schema, $config);

                foreach ($config as $key => $val)
                    Config::geConfigParse('/' . $key, Config::PROTO_GC['gcpRowData'], $config[$key], $errorString);
                break;


            case 'gcpFilterTexts':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array.', $schema, $config);

                foreach ($config as $key => $val)
                    Config::geConfigParse('/' . $key, Config::PROTO_GC['gcpFilterTexts'], $config[$key], $errorString);
                break;


            case 'gcpFilterInts':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array.', $schema, $config);

                foreach ($config as $key => $val)
                    Config::geConfigParse('/' . $key, Config::PROTO_GC['gcpFilterInts'], $config[$key], $errorString);
                break;


            case 'inputs':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array.', $schema, $config);

                foreach ($config as $inputCol => $input) {
                    if (!isset($input['type']) ||
                        !is_string($input['type']) ||
                        !in_array($input['type'], Input::ALLOWED_INPUT_TYPES)
                    ) {
                        Config::geConfigParseError($errorString . '/' . $inputCol . ' should be a valid input type.', $schema, $config);
                    }
                }
                break;


            case 'inputsWhereCol':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array.', $schema, $config);

                foreach ($config as $whereVal => $inputs) {
                    if (!is_array($config))
                        Config::geConfigParseError($errorString . '/' . $whereVal . ' should be an array.', $schema, $config);

                    foreach ($inputs as $inputCol => $input) {
                        if ($inputCol == 'gcMulti') continue;
                        if (!isset($input['type']) ||
                            !is_string($input['type']) ||
                            !in_array($input['type'], Input::ALLOWED_INPUT_TYPES)
                        ) {
                            Config::geConfigParseError($errorString . '/' . $whereVal . '/' . $inputCol . ' should be a valid input type.', $schema, $config);
                        }
                    }
                }
                break;


            case 'inputsWhere':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array.', $schema, $config);

                foreach ($config as $whereKey => $where) {
                    if (!is_array($config))
                        Config::geConfigParseError($errorString . '/' . $whereKey . ' should be an array.', $schema, $config);

                    foreach ($where as $whereVal => $inputs) {
                        if (!is_array($config))
                            Config::geConfigParseError($errorString . '/' . $whereKey . '/' . $whereVal . ' should be an array.', $schema, $config);

                        foreach ($inputs as $inputCol => $input) {
                            if (!isset($input['type']) ||
                                !is_string($input['type']) ||
                                !in_array($input['type'], Input::ALLOWED_INPUT_TYPES)
                            ) {
                                Config::geConfigParseError($errorString . '/' . $whereKey . '/' . $whereVal . '/' . $inputCol . ' should be a valid input type.', $schema, $config);
                            }
                        }
                    }
                }
                break;


            case 'inputsWhereParent':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array.', $schema, $config);

                foreach ($config as $parentKey => $parent) {
                    if (!is_array($config))
                        Config::geConfigParseError($errorString . '/' . $parentKey . ' should be an array.', $schema, $config);

                    foreach ($parent as $parentVal => $where) {
                        if (!is_array($config))
                            Config::geConfigParseError($errorString . '/' . $parentKey . '/' . $parentVal . ' should be an array.', $schema, $config);

                        foreach ($where as $whereVal => $inputs) {
                            if (!is_array($config))
                                Config::geConfigParseError($errorString . '/' . $parentKey . '/' . $parentVal . '/' . $whereVal . ' should be an array.', $schema, $config);

                            foreach ($inputs as $inputCol => $input) {
                                if ($inputCol == 'gcMulti') continue;
                                if (!isset($input['type']) ||
                                    !is_string($input['type']) ||
                                    !in_array($input['type'], Input::ALLOWED_INPUT_TYPES)
                                ) {
                                    Config::geConfigParseError($errorString . '/' . $parentKey . '/' . $parentVal . '/' . $whereVal . '/' . $inputCol . ' should be a valid input type.', $schema, $config);
                                }
                            }
                        }
                    }
                }
                break;


            case 'moduleMultiple':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array.', $schema, $config);

                foreach ($config as $key => $val)
                    Config::geConfigParse('/' . $key, Config::PROTO_GC['gcpModuleMultiple'], $config[$key], $errorString);

                break;


            case 'options':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array.', $schema, $config);

                foreach ($config as $key => $val) {
                    if (!is_array($val))
                        Config::geConfigParseError($errorString . '/' . $key . ' should be an array.', $schema, $config);

                    if (!isset($val['label']))
                        Config::geConfigParseError($errorString . '/' . $key . '/label missing.', $schema, $config);
                }
                break;


            case 'gcpModules':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array.', $schema, $config);

                foreach ($config as $key => $val)
                    Config::geConfigParse('/' . $key, Config::PROTO_GC['gcpModules'], $config[$key], $errorString);

                break;


            case 'gcpImagesInUse':
                if (!is_array($config))
                    Config::geConfigParseError($errorString . ' should be an array.', $schema, $config);

                foreach ($config as $key => $val)
                    Config::geConfigParse('/' . $key, Config::PROTO_GC['gcpImagesInUse'], $config[$key], $errorString);

                break;


            case 'gcpVisit':
                switch (gettype($config)) {
                    case 'boolean':
                    case 'integer':
                        break;

                    case 'array':
                        foreach ($config as $key => $val) {
                            if (!isset($config[$key]['id']))
                                Config::geConfigParseError($errorString . '/' . $val . ' should have an id.', $schema, $config);
                            if (!isset($config[$key]['prefix']))
                                Config::geConfigParseError($errorString . '/' . $val . ' should have a prefix.', $schema, $config);
                        }
                        break;

                    default:
                        Config::geConfigParseError($errorString . ' should be false, int or array.', $schema, $config);
                        break;
                }
                break;


            default:
                Config::geConfigParseError($errorString . ' invalid config type.', $schema, $config);
                break;
        }
    }




    private static function geConfigParseError() {
        geD(func_get_args());
        geErrorPage(500, 'config error');
    }



    static function getImageTypes(): ?array {
        foreach (E::$conf as $key => $confPage) {
            if ($confPage['gcPageType'] == 'gcpImages') {
                return $confPage['gcImageTypes'];
            }
        }

        return null;
    }

}
