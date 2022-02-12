<?php
/* Copyright 2017-2021 Ino Detelić & Zaloa G. Ramos

 - Licensed under the EUPL, Version 1.2 only (the "Licence");
 - You may not use this work except in compliance with the Licence.

 - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

 - Unless required by applicable law or agreed to in writing, software distributed
   under the Licence is distributed on an "AS IS" basis,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 - See the Licence for the specific language governing permissions and limitations under the Licence.
*/

namespace Galaxia;


class Editor {

    public bool   $debug   = false;
    public string $version = '';

    public string $dir       = '';
    public string $dirLayout = '';
    public string $dirLogic  = '';
    public string $dirView   = '';

    public string $logic     = '';
    public string $view      = '';
    public string $layout    = 'layout-default';
    public string $homeSlug  = 'page';
    public string $imageSlug = 'image';

    public array $locales = [
        'pt' => ['long' => 'pt_PT', 'full' => 'Português'],
        'en' => ['long' => 'en_US', 'full' => 'English'],
        'es' => ['long' => 'es_ES', 'full' => 'Castellano'],
    ];

    public string $translateEndpoint = '';

    public function __construct(string $dir) {
        $this->dir       = rtrim($dir, '/') . '/';
        $this->dirLayout = $this->dir . 'src/GalaxiaEditor/layout/';
        $this->dirLogic  = $this->dir . 'src/GalaxiaEditor/template/';
        $this->dirView   = $this->dir . 'src/GalaxiaEditor/template/';
    }


    static function pageUsers(): array {
        return require dirname(__DIR__, 2) . '/GalaxiaEditor/config/default/gcDefaultUsers.php';
    }

    static function linkUser(): array {
        return require dirname(__DIR__, 2) . '/GalaxiaEditor/config/default/gcDefaultUser.php';
    }

    static function pagePasswords(): array {
        return require dirname(__DIR__, 2) . '/GalaxiaEditor/config/default/gcDefaultPasswords.php';
    }

    static function linkPassword(): array {
        return require dirname(__DIR__, 2) . '/GalaxiaEditor/config/default/gcDefaultPassword.php';
    }

    static function pageHistory(): array {
        return require dirname(__DIR__, 2) . '/GalaxiaEditor/config/default/gcDefaultHistory.php';
    }

    static function pageDev(): array {
        return [
            'gcPerms'      => 'dev',
            'gcPageType'   => 'gcpLinkToItem',
            'gcMenuTitle'  => 'Dev',
            'gcMenuShow'   => ['gcPerms' => ['dev']],
            'geLinkToItem' => ['dev'],
        ];
    }

    static function pageStatsGoaccess(string $logFile, string $dir): array {
        return [
            'gcPageType'    => 'gcpGoaccessStats',
            'gcMenuTitle'   => 'Basic Stats',
            'gcMenuShow'    => true,
            'gcGoaccessLog' => $logFile,
            'gcGoaccessDir' => $dir,
        ];
    }

    static function pageChat(string $name): array {
        return [
            'gcPageType'  => 'gcpChat',
            'gcMenuTitle' => $name,
            'gcMenuShow'  => true,
        ];
    }


    static function linkToItem(string $title, string $root, string $id): array {
        return [
            'gcPageType'   => 'gcpLinkToItem',
            'gcMenuTitle'  => $title,
            'gcMenuShow'   => true,
            'geLinkToItem' => [$root, $id],
        ];
    }


}
