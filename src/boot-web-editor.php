<?php

use Galaxia\ArrayShape;
use Galaxia\Authentication;
use Galaxia\FastRoute;
use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Request;
use Galaxia\Text;
use GalaxiaEditor\config\Config;
use GalaxiaEditor\config\ConfigDb;
use GalaxiaEditor\E;



require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/autoload-editor.php';


E::$req = new Request($_SERVER['SERVER_NAME']);
E::$req->redirectRemoveSlashes();


// init app

$app = G::init($_SERVER['GALAXIA_DIR_APP'] ?? (dirname(dirname(__DIR__)) . '/' . (E::$req->host ?? '')));

G::timerStart('locales');
G::langAddInactive();
G::langSet();
G::timerStop('locales');




// init editor

G::timerStart('editor');
$editor          = G::initEditor(dirname(__DIR__));
E::$conf         = require G::dir() . 'config/editor.php';
$editor->version = '5.0.0-alpha';
G::timerStop('editor');

G::loadTranslations();




// init me

$me = G::initMe();
G::login(E::$req->host);

$db = G::getMysqli();

if (G::isDevDebug()) {
    E::$req->cacheBypass = true;
}




// authentication

$auth = new Authentication();




// routing with fastroute

if (G::isLoggedIn()) {

    // set nginx cache bypass cookie
    if ($app->cookieNginxCacheBypassKey) {
        setcookie(
            $app->cookieNginxCacheBypassKey,
            '1',
            [
                'expires'  => time() + 86400, // 1 day
                'path'     => '/',
                'domain'   => '.' . E::$req->host,
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict',
            ]
        );
    }


    // galaxia chat
    if (isset(E::$conf['chat']) && E::$req->method == 'POST' && in_array(E::$req->host, ['/edit/chat/listen', '/edit/chat/publish']))
        require_once __DIR__ . '/GalaxiaEditor/chat/gChat.php';


    // CSRF
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    if (!G::isDevDebug())
        if (E::$req->method == 'POST')
            if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf'])
                geErrorPage(500, 'invalid csrf token.');


    // set editor language
    if (isset($me->options['Language']))
        if (isset($editor->locales[$me->options['Language']]))
            G::langSet($me->options['Language']);



    // parse editor configuration
    G::timerStart('editor configuration');

    G::timerStart('gecValidateArray');
    Config::validate();
    G::timerStop('gecValidateArray');


    // get editor slugs from config
    $editor->homeSlug = array_key_first(E::$conf) ?? $editor->homeSlug;
    foreach (E::$conf as $rootSlug => $confPage) {
        if ($confPage['gcPageType'] == 'gcpImages') {
            $editor->imageSlug = $rootSlug;
            break;
        }
    }


    // disable input modifiers(gcInputsWhere, gcInputsWhereCol, gcInputsWhereParent) without perms by setting their type to 'none'
    foreach (E::$conf as $rootSlug => $confPage) {
        foreach ($confPage['gcItem']['gcInputsWhere'] ?? [] as $whereKey => $where) {
            foreach ($where as $whereVal => $inputs) {
                foreach ($inputs as $inputKey => $input) {
                    if (!isset($input['gcPerms'])) continue;
                    if (!array_intersect($input['gcPerms'] ?? [], $me->perms)) {
                        E::$conf[$rootSlug]['gcItem']['gcInputsWhere'][$whereKey][$whereVal][$inputKey]['type'] = 'none';
                    }
                }
            }
        }
        foreach ($confPage['gcItem']['gcModules'] ?? [] as $moduleKey => $module) {
            foreach ($module['gcInputsWhereCol'] as $fieldKey => $inputs) {
                foreach ($inputs as $inputKey => $input) {
                    if (!isset($input['gcPerms'])) continue;
                    if (!array_intersect($input['gcPerms'] ?? [], $me->perms)) {
                        E::$conf[$rootSlug]['gcItem']['gcModules'][$moduleKey]['gcInputsWhereCol'][$fieldKey][$inputKey]['type'] = 'none';
                    }
                }
            }
            foreach ($module['gcInputsWhereParent'] as $parentKey => $parent) {
                foreach ($parent as $parentVal => $fields) {
                    foreach ($fields as $fieldKey => $inputs) {
                        foreach ($inputs as $inputKey => $input) {
                            if (!isset($input['gcPerms'])) continue;
                            if (!array_intersect($input['gcPerms'] ?? [], $me->perms)) {
                                E::$conf[$rootSlug]['gcItem']['gcModules'][$moduleKey]['gcInputsWhereParent'][$parentKey][$parentVal][$fieldKey][$inputKey]['type'] = 'none';
                            }
                        }
                    }
                }
            }
        }
    }


    G::timerStart('arrayRemovePermsRecursive()');
    ArrayShape::removePermsRecursive(E::$conf, $me->perms);
    G::timerStop('arrayRemovePermsRecursive()');

    G::timerStart('gecLanguify');
    ArrayShape::languify(E::$conf, array_keys(G::locales()), $me->perms);
    G::timerStop('gecLanguify');


    // remove inputs without type
    foreach (E::$conf as $rootSlug => $confPage) {
        foreach ($confPage['gcItem']['gcInputs'] ?? [] as $inputKey => $input) {
            if (!isset($input['type'])) unset(E::$conf[$rootSlug]['gcItem']['gcInputs'][$inputKey]);
        }
        foreach ($confPage['gcItem']['gcInputsWhere'] ?? [] as $whereKey => $where) {
            foreach ($where as $whereVal => $inputs) {
                foreach ($inputs as $inputKey => $input) {
                    if (!isset($input['type'])) E::$conf[$rootSlug]['gcItem']['gcInputsWhere'][$whereKey][$whereVal][$inputKey]['type'] = 'none';
                }
            }
        }

        foreach ($confPage['gcItem']['gcModules'] ?? [] as $moduleKey => $module) {
            foreach ($module['gcInputs'] as $inputKey => $input) {
                if (!isset($input['type'])) E::$conf[$rootSlug]['gcItem']['gcModules'][$moduleKey]['gcInputs'][$inputKey]['type'] = 'none';
            }
            foreach ($module['gcInputsWhereCol'] as $fieldKey => $inputs) {
                foreach ($inputs as $inputKey => $input) {
                    if (!isset($input['type'])) E::$conf[$rootSlug]['gcItem']['gcModules'][$moduleKey]['gcInputsWhereCol'][$fieldKey][$inputKey]['type'] = 'none';
                }
            }
            foreach ($module['gcInputsWhereParent'] as $parentKey => $parent) {
                foreach ($parent as $parentVal => $fields) {
                    foreach ($fields as $fieldKey => $inputs) {
                        foreach ($inputs as $inputKey => $input) {
                            if (!isset($input['type'])) E::$conf[$rootSlug]['gcItem']['gcModules'][$moduleKey]['gcInputsWhereParent'][$parentKey][$parentVal][$fieldKey][$inputKey]['type'] = 'none';
                        }
                    }
                }
            }
        }
    }


    if (G::isDev()) {
        G::timerStart('gecValidateDatabase');
        ConfigDb::validate();
        G::timerStop('gecValidateDatabase');
    }

    G::timerStop('editor configuration');


    // routes
    G::timerStart('routing');
    $editor->layout = 'layout-editor';
    $dispatcher     = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) use ($me, $editor) {

        $r->get('/edit/{pgSlug:login}', 'redirect-home');
        $r->post('/edit/{pgSlug:login}', 'redirect-home');
        $r->get('/edit/{pgSlug:logout}', 'login/logout');

        if ($me->hasPerm('dev')) {
            $r->get('/edit/{pgSlug:dev}', 'dev/dev');
            $r->get('/edit/dev/{pgSlug:sitemap}', 'dev/sitemap');
            $r->get('/edit/dev/{pgSlug:cacheDeleteApp}', 'dev/cache-delete-app');
            $r->get('/edit/dev/{pgSlug:cacheDeleteAll}', 'dev/cache-delete-all');
            $r->get('/edit/dev/{pgSlug:info}', 'dev/info');
        }
        $r->get('/edit/dev/{pgSlug:cacheDeleteEditor}', 'dev/cache-delete-editor');
        $r->get('/edit/dev/{pgSlug:imageListDeleteResizes}', 'dev/image-list-delete-resizes');
        $r->get('/edit/dev/{pgSlug:imageListDeleteWebp}', 'dev/image-list-delete-webp');
        $r->get('/edit/dev/{pgSlug:imageListReorder}', 'dev/image-list-reorder');

        $r->get('/edit/importer/{pgSlug:jsonld}', 'importer/jsonld');
        $r->get('/edit/importer/{pgSlug:youtube}', 'importer/youtube');
        $r->get('/edit/importer/{pgSlug:vimeo}', 'importer/vimeo');

        foreach (E::$conf as $rootSlug => $confPage) {
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
                    if ($confPage['gcList']['gcLinks']['order'] ?? []) {
                        $r->get('/edit/{pgSlug:' . $rootSlug . '}/{itemId:order}', 'list/list');
                        $r->post('/edit/{pgSlug:' . $rootSlug . '}/{itemId:order}', 'list/order-post');
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

                        if ($confPage['gcImage']['gcDelete'] ?? []) {
                            $r->get('/edit/{pgSlug:' . $editor->imageSlug . '}/delete', 'imageList/deleteMulti');
                            $r->post('/edit/{pgSlug:' . $editor->imageSlug . '}/delete', 'imageList/deleteMulti-post');
                        }

                        $r->get('/edit/{pgSlug:' . $editor->imageSlug . '}/verify', 'imageList/verify');

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
                            $r->get('/edit/{pgSlug:' . $editor->imageSlug . '}/{imgSlug}/{action:deleteWebp}', 'image/image-delete-webp-post');
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

                case 'gcpGoaccessStats':
                    $r->get('/edit/{pgSlug:' . $rootSlug . '}', 'stats/goaccess');
                    $r->get('/edit/{pgSlug:' . $rootSlug . '}/{itemDate}', 'stats/goaccessDate');
                    break;

                default:
                    break;
            }

        }

    });
    G::timerStop('routing');

    $routeInfo = $dispatcher->dispatch(E::$req->method, E::$req->path);

    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            geErrorPage(404, __FILE__ . ':' . __LINE__);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            geErrorPage(403, __FILE__ . ':' . __LINE__);
            break;
        case FastRoute\Dispatcher::FOUND:
            extract($routeInfo[2]); // make php $variables with names and values defined in the routing above.

            E::$pgSlug   = $routeInfo[2]['pgSlug'] ?? '';
            E::$tabName  = $routeInfo[2]['tabName'] ?? '';
            E::$tabId    = $routeInfo[2]['tabId'] ?? '';
            E::$itemId   = $routeInfo[2]['itemId'] ?? '';
            E::$imgSlug  = $routeInfo[2]['imgSlug'] ?? '';
            E::$imgW     = $routeInfo[2]['imgW'] ?? '';
            E::$imgH     = $routeInfo[2]['imgH'] ?? '';
            E::$action   = $routeInfo[2]['action'] ?? '';
            E::$itemDate = $routeInfo[2]['itemDate'] ?? '';

            if (isset(E::$conf[E::$pgSlug])) {
                E::$section = &E::$conf[E::$pgSlug];
            } else {
                E::$section = [];
            }

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

    $routeInfo = $dispatcher->dispatch(E::$req->method, E::$req->path);

    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            G::errorPage(404, __FILE__ . ':' . __LINE__);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            G::errorPage(403, __FILE__ . ':' . __LINE__);
            break;
        case FastRoute\Dispatcher::FOUND:
            extract($routeInfo[2]); // make php $variables with names and values defined in the routing above.
            E::$pgSlug     = $routeInfo[2]['pgSlug'];
            $editor->logic = $editor->view = $routeInfo[1];
            break;
    }

}




// custom redirects

if ($routeInfo[1] == 'redirect-home') G::redirect('edit/' . $editor->homeSlug, 303);




// Include the logic part of the template.
// - logic can modify which layout and template are going to be used

G::timerStart('logic');
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
G::timerStop('logic');




// POST actions

if (E::$req->method == 'POST' && Flash::hasError()) {
    Flash::error(Text::t('Form errors found.'));
}




// Exit on missing layouts or template view

if (!file_exists($editor->dirLayout . $editor->layout . '.phtml')) {
    G::errorPage(500, 'missing layout: ' . Text::h($editor->layout));
}
if (!file_exists($editor->dirView . $editor->view . '.phtml')) {
    G::errorPage(500, 'missing template view: ' . $editor->dir . 'src/templates/' . $editor->view);
}




// Include (run) the current layout. the layout includes the template (dynamic part of webpage)
G::timerStart('layout');
include $editor->dirLayout . $editor->layout . '.phtml';
G::timerStop('layout');

if (G::isDev() && $editor->layout != 'none') {
    G::timerPrint(true, true);
}


exit();
