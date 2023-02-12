<?php
// Copyright 2017-2023 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace GalaxiaEditor;


use Galaxia\Authentication;
use Galaxia\Pagination;


class E {

    static Authentication $auth;

    static array $conf    = [];
    static array $section = [];

    static string $pgTitle = '';
    static string $hdTitle = '';

    static string $pgSlug   = '';
    static string $tabName  = '';
    static string $tabId    = '';
    static string $itemId   = '';
    static string $imgSlug  = '';
    static string $imgW     = '';
    static string $imgH     = '';
    static string $action   = '';
    static string $itemDate = '';

    static string $hookTranslate = '';

    static array $langSelectClass    = [];
    static bool  $includeTrix        = false;
    static bool  $showSwitchesLang   = false;
    static bool  $passwordColsFound  = false;
    static bool  $chatInclude        = false;
    static bool  $chatIncludeCurrent = false;

    static array $pageById = [];

    static Pagination $pagination;

    // login
    static array $loginInputs = [];

    // list
    static array  $listColumns = [];
    static array  $listRows    = [];
    static string $listOrder   = '';

    // item
    static array  $item              = [];
    static array  $itemChanges       = [];
    static string $uniqueId;
    static array  $modules           = [];
    static array  $module            = [];
    static int    $moduleKey         = 0;
    static array  $fieldsNew         = [];
    static array  $fieldsDel         = [];
    static array  $fieldsUpd         = [];
    static string $firstStatus       = '';
    static array  $itemPostModule    = [];
    static int    $itemPostModuleKey = 0;

    static array $siblings = [];
    static int   $prev     = 0;
    static int   $next     = 0;

    // image
    static array $img        = [];
    static array $imgChanges = [];
    static array $imgInputs  = [];
    static array $imgRows    = [];
    static array $imgItems   = [];

    // history
    static array $historyItems       = [];
    static array $historyPageNames   = [];
    static array $historyInputKeys   = [];
    static array $historyRootSlugs   = [];
    static array $historyStatusNames = [];
    static array $historyUserNames   = [];
    static array $historyRows        = [];

    // error
    static int    $errorCode = 0;
    static string $error     = '';

    // stats
    static array $statsGoaccess = [];

}
