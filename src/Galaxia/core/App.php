<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

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
        $this->dir      = $dir . '/';
        $this->dirCache = $this->dir . 'var/cache/';
        $this->dirLog   = $this->dir . 'var/log/';
        $this->dirImage = $this->dir . 'var/media/image/';
    }

}
