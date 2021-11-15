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


use Galaxia\Request;


class E {

    static array $conf    = [];
    static array $section = [];

    static string $pgSlug   = ''; // pgSlug
    static string $tabName  = ''; // tabName
    static string $tabId    = ''; // tabId
    static string $itemId   = ''; // itemId
    static string $imgSlug  = ''; // imgSlug
    static string $imgW     = ''; // imgW
    static string $imgH     = ''; // imgH
    static string $action   = ''; // action
    static string $itemDate = ''; // itemDate

    static Request $req;

    static bool $includeTrix        = false;
    static bool $showSwitchesLang   = false;
    static bool $passwordColsFound  = false;
    static bool $chatInclude        = false;
    static bool $chatIncludeCurrent = false;

    static string $pgTitle = '';
    static string $hdTitle = '';

    static array $itemChanges = [];

}
