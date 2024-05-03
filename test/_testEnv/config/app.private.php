<?php
// Copyright 2017-2023 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12


// We're inside G::init();

use Galaxia\G;

G::$app->mysqlDb   = '_galaxiaEditorTest';
G::$app->mysqlUser = 'root';
G::$app->mysqlPass = '';

G::$app->cookieEditorKey           = 'galaxiaEditor_testDoNotUse';
G::$app->cookieNginxCacheBypassKey = 'galaxiaCacheBypass_testDoNotUse';
G::$app->cookieDebugKey            = 'galaxiaDebug_testDoNotUse';
G::$app->cookieDebugVal            = '_testDoNotUse';
