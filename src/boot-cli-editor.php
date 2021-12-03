<?php


use Galaxia\G;
use Galaxia\Request;


require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/autoload-editor.php';

G::$req = new Request($_SERVER['SERVER_NAME'] ?? 'galaxia.editor');
