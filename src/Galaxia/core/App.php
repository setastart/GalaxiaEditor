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


class App {

    public string $dir       = '';
    public string $dirLog    = '';
    public string $dirCache  = '';
    public string $dirImage  = '';
    public string $urlImages = '/media/image/';

    public array $routes = [];

    public array $locale  = ['url' => '/', 'long' => 'en_US', 'full' => 'English'];
    public array $locales = [
        'en' => ['url' => '/', 'long' => 'en_US', 'full' => 'English'],
    ];

    public array $localesInactive = [];

    public array  $langs    = ['en'];
    public string $lang     = 'en';
    public string $timeZone = 'Europe/Lisbon';

    public string $cookieEditorKey           = 'galaxiaEditor';
    public string $cookieNginxCacheBypassKey = '';
    public string $cookieDebugKey            = 'debug';
    public string $cookieDebugVal            = '';

    public string $mysqlHost = '127.0.0.1';
    public string $mysqlDb   = '';
    public string $mysqlUser = '';
    public string $mysqlPass = '';

    public function __construct(string $dir) {
        $this->dir      = rtrim($dir, '/') . '/';
        $this->dirCache = $this->dir . 'var/cache/';
        $this->dirLog   = $this->dir . 'var/log/';
        $this->dirImage = $this->dir . 'var/media/image/';
    }

}
