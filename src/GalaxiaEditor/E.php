<?php
/**
 * Copyright 2017-2021 Ino Detelić & Zaloa G. Ramos
 *
 * - Licensed under the EUPL, Version 1.2 only (the "Licence");
 * - You may not use this work except in compliance with the Licence.
 *
 * - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12
 *
 * - Unless required by applicable law or agreed to in writing, software distributed
 * under the Licence is distributed on an "AS IS" basis,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * - See the Licence for the specific language governing permissions and limitations under the Licence.
 */

namespace GalaxiaEditor;


use Galaxia\Authentication;


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

    static bool $includeTrix        = false;
    static bool $showSwitchesLang   = false;
    static bool $passwordColsFound  = false;
    static bool $chatInclude        = false;
    static bool $chatIncludeCurrent = false;

    static array $itemChanges = [];
    static array $pageById = [];

}
