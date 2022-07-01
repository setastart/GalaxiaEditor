<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\Authentication;
use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Request;
use Galaxia\Text;
use GalaxiaEditor\E;


require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/autoload-editor.php';


// init app

G::$req = new Request($_SERVER['SERVER_NAME']);
G::$req->redirectRemoveSlashes();

G::init($_SERVER['GALAXIA_DIR_APP'] ?? (dirname(__DIR__, 2) . '/' . (G::$req->host ?? '')));

G::langAddInactive();
G::langSet();




// init editor

G::initEditor(dirname(__DIR__));

G::$editor->version = '5.48.0';




// init me

G::login();
// G::$me->updateLastOnline();

if (G::isDevDebug()) {
    G::$req->cacheBypass = true;
}

E::$auth = new Authentication();

G::loadTranslations(withEditor: true);




// routing

if (G::isLoggedIn()) {
    require __DIR__ . '/route-logged-in.php';
} else {
    require __DIR__ . '/route-logged-out.php';
}




// Include the logic part of the template.
// - logic can modify which layout and template are going to be used

G::timerStart('Logic');
G::timerStart('_autorun.php');
require __DIR__ . '/GalaxiaEditor/template/_autorun.php';
G::timerStop('_autorun.php');

$logicExploded  = explode('/', G::$editor->logic);
$logicPathCount = count($logicExploded);
$logicPath      = '';
for ($i = 0; $i < $logicPathCount - 1; $i++) {
    $logicPath .= ($logicExploded[$i] . '/');
    if (file_exists(G::$editor->dirLogic . $logicPath . '_autorun.php')) {
        G::timerStart($logicPath . '_autorun.php');
        include G::$editor->dirLogic . $logicPath . '_autorun.php';
        G::timerStop($logicPath . '_autorun.php');
    }
}
if (file_exists(G::$editor->dirLogic . G::$editor->logic . '.php')) {
    G::timerStart(G::$editor->logic . '.php');
    include G::$editor->dirLogic . G::$editor->logic . '.php';
    G::timerStop(G::$editor->logic . '.php');
}
G::timerStop('Logic');




// POST actions

if (G::$req->method == 'POST' && Flash::hasError()) {
    Flash::error(Text::t('Form errors found.'));
}




// Exit on missing layouts or template view

if (!file_exists(G::$editor->dirLayout . G::$editor->layout . '.phtml')) {
    G::errorPage(500, 'missing layout: ' . Text::h(G::$editor->layout));
}
if (!file_exists(G::$editor->dirView . G::$editor->view . '.phtml')) {
    G::errorPage(500, 'missing template view: ' . G::$editor->dir . 'src/templates/' . G::$editor->view);
}




// Include (run) the current layout. the layout includes the template (dynamic part of webpage)

G::timerStart('Layout');
include G::$editor->dirLayout . G::$editor->layout . '.phtml';
G::timerStop('Layout');

if (G::isDev() && G::$editor->layout != 'layout-none') {
    G::timerPrint(true, true);
}


exit();
