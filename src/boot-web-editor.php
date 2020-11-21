<?php

use Galaxia\{ArrayShape, Authentication, Director, FastRoute, Flash, Text};
use GalaxiaEditor\config\Config;
use GalaxiaEditor\config\ConfigDb;


// redirect to url without trailing slashes

if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] != '/' && substr($_SERVER['REQUEST_URI'], -1, 1) == '/') {
    $_SERVER['REQUEST_URI'] = rtrim($_SERVER['REQUEST_URI'], '/');
    if ($_SERVER['REQUEST_URI'] == '') $_SERVER['REQUEST_URI'] = '/';
    header('Location: ' . $_SERVER['REQUEST_URI'], true, 302);
    exit();
}




// autoload

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/autoload-editor.php';




// init app

$app = Director::init($_SERVER['GALAXIA_DIR_APP'] ?? (dirname(dirname(__DIR__)) . '/' . ($_SERVER['SERVER_NAME'] ?? '')));


Director::timerStart('locales');
foreach ($app->localesInactive as $lang => $locale) {
    if (isset($app->locales[$lang])) continue;
    $app->locales[$lang] = $locale;
    $app->langs          = array_keys($app->locales);
}
$app->setLang();
Director::timerStop('locales');




// init editor

Director::timerStart('editor');
$editor = Director::initEditor(dirname(__DIR__));
$geConf = [];
require $app->dir . 'config/editor.php';
$editor->version = '4.19.0';
Director::timerStop('editor');

Director::loadTranslations();




// init me

$me = Director::initMe();
$me->logInFromCookieSessionId($app->cookieEditorKey);

$db = Director::getMysqli();

if (Director::isDevDebug()) {
    $app->cacheBypass = true;
}




// authentication

$auth = new Authentication();




// routing with fastroute

if ($me->loggedIn) {

    // set nginx cache bypass cookie
    if ($app->cookieNginxCacheBypassKey) {
        setcookie(
            $app->cookieNginxCacheBypassKey,
            '1',
            [
                'expires'  => time() + 86400, // 1 day
                'path'     => '/',
                'domain'   => '.' . $_SERVER['SERVER_NAME'],
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }


    // galaxia chat
    if (isset($geConf['chat']) && $_SERVER['REQUEST_METHOD'] == 'POST' && in_array($_SERVER['REQUEST_URI'], ['/edit/chat/listen', '/edit/chat/publish']))
        require __DIR__ . '/GalaxiaEditor/chat/gChat.php';


    // CSRF
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    if (!Director::isDevDebug())
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
            if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf'])
                geErrorPage(500, 'invalid csrf token.');


    // set editor language
    if (isset($me->options['Language']))
        if (isset($editor->locales[$me->options['Language']]))
            $app->setLang($me->options['Language']);



    // parse editor configuration
    Director::timerStart('editor configuration');

    Director::timerStart('gecValidateArray');
    Config::validate($geConf);
    Director::timerStop('gecValidateArray');


    // get editor slugs from config
    $editor->homeSlug = array_key_first($geConf) ?? $editor->homeSlug;
    foreach ($geConf as $rootSlug => $confPage) {
        if ($confPage['gcPageType'] == 'gcpImages') {
            $editor->imageSlug = $rootSlug;
            break;
        }
    }


    // disable input modifiers(gcInputsWhere, gcInputsWhereCol, gcInputsWhereParent) without perms by setting their type to 'none'
    foreach ($geConf as $rootSlug => $confPage) {
        foreach ($confPage['gcItem']['gcInputsWhere'] ?? [] as $whereKey => $where) {
            foreach ($where as $whereVal => $inputs) {
                foreach ($inputs as $inputKey => $input) {
                    if (!isset($input['gcPerms'])) continue;
                    if (!array_intersect($input['gcPerms'] ?? [], $me->perms)) {
                        $geConf[$rootSlug]['gcItem']['gcInputsWhere'][$whereKey][$whereVal][$inputKey]['type'] = 'none';
                    }
                }
            }
        }
        foreach ($confPage['gcItem']['gcModules'] ?? [] as $moduleKey => $module) {
            foreach ($module['gcInputsWhereCol'] as $fieldKey => $inputs) {
                foreach ($inputs as $inputKey => $input) {
                    if (!isset($input['gcPerms'])) continue;
                    if (!array_intersect($input['gcPerms'] ?? [], $me->perms)) {
                        $geConf[$rootSlug]['gcItem']['gcModules'][$moduleKey]['gcInputsWhereCol'][$fieldKey][$inputKey]['type'] = 'none';
                    }
                }
            }
            foreach ($module['gcInputsWhereParent'] as $parentKey => $parent) {
                foreach ($parent as $parentVal => $fields) {
                    foreach ($fields as $fieldKey => $inputs) {
                        foreach ($inputs as $inputKey => $input) {
                            if (!isset($input['gcPerms'])) continue;
                            if (!array_intersect($input['gcPerms'] ?? [], $me->perms)) {
                                $geConf[$rootSlug]['gcItem']['gcModules'][$moduleKey]['gcInputsWhereParent'][$parentKey][$parentVal][$fieldKey][$inputKey]['type'] = 'none';
                            }
                        }
                    }
                }
            }
        }
    }


    Director::timerStart('arrayRemovePermsRecursive()');
    ArrayShape::removePermsRecursive($geConf, $me->perms);
    Director::timerStop('arrayRemovePermsRecursive()');

    Director::timerStart('gecLanguify');
    ArrayShape::languify($geConf, array_keys($app->locales), $me->perms);
    Director::timerStop('gecLanguify');


    // remove inputs without type
    foreach ($geConf as $rootSlug => $confPage) {
        foreach ($confPage['gcItem']['gcInputs'] ?? [] as $inputKey => $input) {
            if (!isset($input['type'])) unset($geConf[$rootSlug]['gcItem']['gcInputs'][$inputKey]);
        }
        foreach ($confPage['gcItem']['gcInputsWhere'] ?? [] as $whereKey => $where) {
            foreach ($where as $whereVal => $inputs) {
                foreach ($inputs as $inputKey => $input) {
                    if (!isset($input['type'])) $geConf[$rootSlug]['gcItem']['gcInputsWhere'][$whereKey][$whereVal][$inputKey]['type'] = 'none';
                }
            }
        }

        foreach ($confPage['gcItem']['gcModules'] ?? [] as $moduleKey => $module) {
            foreach ($module['gcInputs'] as $inputKey => $input) {
                if (!isset($input['type'])) $geConf[$rootSlug]['gcItem']['gcModules'][$moduleKey]['gcInputs'][$inputKey]['type'] = 'none';
            }
            foreach ($module['gcInputsWhereCol'] as $fieldKey => $inputs) {
                foreach ($inputs as $inputKey => $input) {
                    if (!isset($input['type'])) $geConf[$rootSlug]['gcItem']['gcModules'][$moduleKey]['gcInputsWhereCol'][$fieldKey][$inputKey]['type'] = 'none';
                }
            }
            foreach ($module['gcInputsWhereParent'] as $parentKey => $parent) {
                foreach ($parent as $parentVal => $fields) {
                    foreach ($fields as $fieldKey => $inputs) {
                        foreach ($inputs as $inputKey => $input) {
                            if (!isset($input['type'])) $geConf[$rootSlug]['gcItem']['gcModules'][$moduleKey]['gcInputsWhereParent'][$parentKey][$parentVal][$fieldKey][$inputKey]['type'] = 'none';
                        }
                    }
                }
            }
        }
    }


    if (Director::isDev()) {
        Director::timerStart('gecValidateDatabase');
        ConfigDb::validate($geConf);
        Director::timerStop('gecValidateDatabase');
    }

    Director::timerStop('editor configuration');


    // routes
    Director::timerStart('routing');
    $editor->layout = 'layout-editor';
    $dispatcher     = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) use ($geConf, $me, $editor) {

        $r->get('/edit/{pgSlug:login}', 'redirect-home');
        $r->post('/edit/{pgSlug:login}', 'redirect-home');
        $r->get('/edit/{pgSlug:logout}', 'login/logout');

        if ($me->hasPerm('dev')) {
            $r->get('/edit/{pgSlug:dev}', 'dev/dev');
            $r->get('/edit/dev/{pgSlug:sitemap}', 'dev/sitemap');
            $r->get('/edit/dev/{pgSlug:cacheDeleteApp}', 'dev/cache-delete-app');
            $r->get('/edit/dev/{pgSlug:cacheDeleteAll}', 'dev/cache-delete-all');
        }
        $r->get('/edit/dev/{pgSlug:cacheDeleteEditor}', 'dev/cache-delete-editor');
        $r->get('/edit/dev/{pgSlug:imageListDeleteResizes}', 'dev/image-list-delete-resizes');
        $r->get('/edit/dev/{pgSlug:imageListReorder}', 'dev/image-list-reorder');

        $r->get('/edit/importer/{pgSlug:jsonld}', 'importer/jsonld');
        $r->get('/edit/importer/{pgSlug:youtube}', 'importer/youtube');
        $r->get('/edit/importer/{pgSlug:vimeo}', 'importer/vimeo');

        foreach ($geConf as $rootSlug => $confPage) {
            if (!isset($confPage['gcPageType']) || !is_string($confPage['gcPageType'])) continue;
            switch ($confPage['gcPageType']) {
                case 'gcpHistory':
                    $r->get('/edit/{pgSlug:' . $rootSlug . '}', 'history/list');
                    $r->post('/edit/{pgSlug:' . $rootSlug . '}', 'history/list');
                    $r->get('/edit/{pgSlug:' . $rootSlug . '}/{tabName}/{tabId}', 'history/item');
                    break;

                case 'gcpListItem':
                    if ($confPage['gcList']) {
                        $r->get('/edit/{pgSlug:' . $rootSlug . '}', 'list/list');
                        $r->post('/edit/{pgSlug:' . $rootSlug . '}', 'list/list');
                    }

                    if ($confPage['gcItem']) {
                        if ($confPage['gcItem']['gcInsert']) {
                            $r->get('/edit/{pgSlug:' . $rootSlug . '}/{itemId:new}', 'item/new/new');
                            $r->post('/edit/{pgSlug:' . $rootSlug . '}/{itemId:new}', 'item/new/new-post');
                        }
                        if ($confPage['gcItem']['gcSelect']) {
                            $r->get('/edit/{pgSlug:' . $rootSlug . '}/{itemId:\d+}', 'item/item');
                        }
                        if ($confPage['gcItem']['gcUpdate']) {
                            $r->post('/edit/{pgSlug:' . $rootSlug . '}/{itemId:\d+}', 'item/item-post');
                            if ($me->hasPerm('dev')) {
                                $r->get('/edit/{pgSlug:' . $rootSlug . '}/{itemId:\d+}/history', 'item/item-post-save-history');
                            }
                        }
                        if ($confPage['gcItem']['gcDelete']) {
                            $r->get('/edit/{pgSlug:' . $rootSlug . '}/{itemId:\d+}/delete', 'item/delete/delete');
                            $r->post('/edit/{pgSlug:' . $rootSlug . '}/{itemId:\d+}/delete', 'item/delete/delete-post');
                        }
                    }
                    break;

                case 'gcpImages':
                    if ($confPage['gcImageList']) {
                        $r->get('/edit/{pgSlug:' . $editor->imageSlug . '}', 'imageList/list');
                        $r->post('/edit/{pgSlug:' . $editor->imageSlug . '}', 'imageList/list');
                        $r->get('/edit/{pgSlug:' . $editor->imageSlug . '}/{imgSlug}/resize/{imgW}/{imgH}', 'image/resize/resize-request');
                    }

                    if ($confPage['gcImage']) {
                        if ($confPage['gcImage']['gcInsert']) {
                            $r->get('/edit/{pgSlug:' . $editor->imageSlug . '}/{imgSlug:new}', 'image/new/new');
                            $r->post('/edit/{pgSlug:' . $editor->imageSlug . '}/{imgSlug:new}', 'image/new/new-post');
                        }
                        if ($confPage['gcImage']['gcSelect']) {
                            $r->get('/edit/{pgSlug:' . $editor->imageSlug . '}/{imgSlug}', 'image/image');
                        }
                        if ($confPage['gcImage']['gcUpdate']) {
                            $r->post('/edit/{pgSlug:' . $editor->imageSlug . '}/{imgSlug}', 'image/image-post');
                            $r->get('/edit/{pgSlug:' . $editor->imageSlug . '}/{imgSlug}/{action:deleteResizes}', 'image/image-delete-resizes-post');
                            $r->get('/edit/{pgSlug:' . $editor->imageSlug . '}/{imgSlug}/{action:replace}', 'image/replace/replace');
                            $r->post('/edit/{pgSlug:' . $editor->imageSlug . '}/{imgSlug}/{action:replace}', 'image/replace/replace-post');
                            $r->get('/edit/{pgSlug:' . $editor->imageSlug . '}/{imgSlug}/{action:resize}', 'image/resize/resize');
                            $r->post('/edit/{pgSlug:' . $editor->imageSlug . '}/{imgSlug}/{action:resize}', 'image/resize/resize-post');
                        }
                        if ($confPage['gcImage']['gcDelete']) {
                            $r->get('/edit/{pgSlug:' . $editor->imageSlug . '}/{imgSlug}/{action:delete}', 'image/delete/delete');
                            $r->post('/edit/{pgSlug:' . $editor->imageSlug . '}/{imgSlug}/{action:delete}', 'image/delete/delete-post');
                        }
                    }
                    break;

                case 'gcpChat':
                    $r->get('/edit/{pgSlug:' . $rootSlug . '}', 'chat/room');
                    break;

                default:
                    break;
            }

        }

    });
    Director::timerStop('routing');

    $routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            geErrorPage(404, __FILE__ . ':' . __LINE__);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            geErrorPage(403, __FILE__ . ':' . __LINE__);
            break;
        case FastRoute\Dispatcher::FOUND:
            extract($routeInfo[2]); // make php $variables with names and values defined in the routing above.
            $editor->logic = $editor->view = $routeInfo[1];
            break;
    }

} else {

    $editor->layout = 'layout-default';
    $dispatcher     = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {

        $r->get('/edit/{pgSlug:login}', 'login/login');
        $r->post('/edit/{pgSlug:login}', 'login/login-post');
        $r->get('/edit/{pgSlug:logout}', 'login/logout');

    });

    $routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            Director::errorPage(404, __FILE__ . ':' . __LINE__);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            Director::errorPage(403, __FILE__ . ':' . __LINE__);
            break;
        case FastRoute\Dispatcher::FOUND:
            extract($routeInfo[2]); // make php $variables with names and values defined in the routing above.
            $pgSlug        = $routeInfo[2]['pgSlug'];
            $editor->logic = $editor->view = $routeInfo[1];
            break;
    }

}




// custom redirects

if ($routeInfo[1] == 'redirect-home') Director::redirect('edit/' . $editor->homeSlug, 303);




// Include the logic part of the template.
// - logic can modify which layout and template are going to be used

Director::timerStart('logic');
$logicExploded  = explode('/', $editor->logic);
$logicPathCount = count($logicExploded);
$logicPath      = '';
if (file_exists($editor->dirLogic . '_autorun.php')) {
    include $editor->dirLogic . '_autorun.php';
}
for ($i = 0; $i < $logicPathCount - 1; $i++) {
    $logicPath .= ($logicExploded[$i] . '/');
    if (file_exists($editor->dirLogic . $logicPath . '_autorun.php')) {
        include $editor->dirLogic . $logicPath . '_autorun.php';
    }
}
if (file_exists($editor->dirLogic . $editor->logic . '.php')) {
    include $editor->dirLogic . $editor->logic . '.php';
}
Director::timerStop('logic');




// POST actions

if ($_SERVER['REQUEST_METHOD'] == 'POST' && Flash::hasError()) {
    Flash::error(Text::t('Form errors found.'));
}




// Exit on missing layouts or template view

if (!file_exists($editor->dirLayout . $editor->layout . '.phtml')) {
    Director::errorPage(500, 'missing layout: ' . Text::h($editor->layout));
}
if (!file_exists($editor->dirView . $editor->view . '.phtml')) {
    Director::errorPage(500, 'missing template view: ' . $editor->dir . 'src/templates/' . $editor->view);
}




// Include (run) the current layout. the layout includes the template (dynamic part of webpage)
Director::timerStart('layout');
include $editor->dirLayout . $editor->layout . '.phtml';
Director::timerStop('layout');

if (Director::isDev()) {
    Director::timerPrint(true, true);
}


exit();
