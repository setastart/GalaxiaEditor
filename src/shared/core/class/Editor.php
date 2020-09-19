<?php
/* Copyright 2017-2020 Ino Detelić

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

    public $debug = false;
    public $version = '';

    public $dir       = '';
    public $dirLayout = '';
    public $dirLogic  = '';
    public $dirView   = '';

    public $logic  = '';
    public $view   = '';
    public $layout = 'layout-default';
    public $homeSlug = 'page';
    public $imageSlug = 'image';

    public $locales = [
        'pt' => ['long' => 'pt_PT', 'full' => 'Português'],
        'en' => ['long' => 'en_US', 'full' => 'English'],
        'es' => ['long' => 'es_ES', 'full' => 'Castellano'],
    ];




    public function __construct(string $dir) {
        $this->dir = rtrim($dir, '/') . '/';
        $this->dirLayout = $this->dir . 'src/layout/';
        $this->dirLogic  = $this->dir . 'src/template/';
        $this->dirView   = $this->dir . 'src/template/';
    }

}
