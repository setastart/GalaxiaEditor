<?php

/**
 * This configuration will be read and overlaid on top of the
 * default configuration. Command line arguments will be applied
 * after this file is read.
 */
return [
    // Supported values: `'5.6'`, `'7.0'`, `'7.1'`, `'7.2'`, `'7.3'`, `null`.
    // If this is set to `null`,
    // then Phan assumes the PHP version which is closest to the minor version
    // of the php executable used to execute Phan.
    //
    // Note that the **only** effect of choosing `'5.6'` is to infer
    // that functions removed in php 7.0 exist.
    // (See `backward_compatibility_checks` for additional options)
    // TODO: Set this.
    'target_php_version' => null,

    'file_list' => [
        'src/boot-web-editor.php',
        'src/template/_autorun.php',
    ],

    // A list of directories that should be parsed for class and
    // method information. After excluding the directories
    // defined in exclude_analysis_directory_list, the remaining
    // files will be statically analyzed for errors.
    //
    // Thus, both first-party and third-party code being used by
    // your application should be included in this list.
    'directory_list' => [
        '../_galaxiaComposer/vendor',
        'src/function',
        // 'src/include'
        'src/template'
        // 'src/layout'
    ],

    // A regex used to match every file name that you want to
    // exclude from parsing. Actual value will exclude every
    // "test", "tests", "Test" and "Tests" folders found in
    // "vendor/" directory.
    'exclude_file_regex' => '@^vendor/.*/(tests?|Tests?)/@',

    // A directory list that defines files that will be excluded
    // from static analysis, but whose class and method
    // information should be included.
    //
    // Generally, you'll want to include the directories for
    // third-party code (such as "vendor/") in this list.
    //
    // n.b.: If you'd like to parse but not analyze 3rd
    //       party code, directories containing that code
    //       should be added to both the `directory_list`
    //       and `exclude_analysis_directory_list` arrays.
    'exclude_analysis_directory_list' => [
        '../_galaxiaComposer/vendor',
    ],

    'analyzed_file_extensions' => ['php', 'inc'],

    // Override to hardcode existence and types of (non-builtin) globals.
    // Class names should be prefixed with '\\'.
    // (E.g. ['_FOO' => '\\FooClass', 'page' => '\\PageClass', 'userId' => 'int'])
    'globals_type_map' => [
        'app'     => '\Galaxia\App',
        'editor'  => '\Galaxia\Editor',
        'me'      => '\Galaxia\User',
        'pgSlug'  => 'string',
        'tabName' => 'string',
        'tabId'   => 'string',
        'geConf'  => 'array',
    ],

    'suppress_issue_types' => [
        'PhanTypeNonVarPassByRef',
    ],
    // 'ignore_undeclared_variables_in_global_scope' => true

];
