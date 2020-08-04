<?php



use build\Js;


if (php_sapi_name() == 'cli') require dirname(__DIR__) . '/boot-cli-editor.php';


Js::build();
