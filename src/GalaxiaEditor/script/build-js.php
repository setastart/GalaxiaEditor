<?php


use GalaxiaEditor\build\Js;


if (PHP_SAPI == 'cli') require_once dirname(__DIR__, 2) . '/boot-cli-editor.php';


Js::build();
