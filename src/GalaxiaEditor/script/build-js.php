<?php


use GalaxiaEditor\build\Js;


if (php_sapi_name() == 'cli') require_once dirname(dirname(__DIR__)) . '/boot-cli-editor.php';


Js::build();
