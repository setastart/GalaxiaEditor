<?php


use GalaxiaEditor\build\Js;


if (php_sapi_name() == 'cli') require_once dirname(__DIR__, 2) . '/boot-cli-editor.php';


Js::build();
