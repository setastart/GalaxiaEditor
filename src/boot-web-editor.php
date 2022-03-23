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


G::$req = new Request($_SERVER['SERVER_NAME']);
G::$req->redirectRemoveSlashes();


// init app

G::init($_SERVER['GALAXIA_DIR_APP'] ?? (dirname(__DIR__, 2) . '/' . (G::$req->host ?? '')));

G::timerStart('locales');
G::langAddInactive();
G::langSet();
G::timerStop('locales');




// init editor

G::timerStart('editor');
G::initEditor(dirname(__DIR__));
E::$conf = require G::dir() . 'config/editor.php';

G::$editor->version = '5.11.0';

G::timerStop('editor');

G::loadTranslations();




// init me

G::login();

if (G::isDevDebug()) {
    G::$req->cacheBypass = true;
}

E::$auth = new Authentication();




// routing with fastroute

if (G::isLoggedIn()) {

    // set nginx cache bypass cookie
    if (G::$app->cookieNginxCacheBypassKey) {
        setcookie(
            G::$app->cookieNginxCacheBypassKey,
            '1',
            [
                'expires'  => time() + 86400, // 1 day
                'path'     => '/',
                'secure'   => G::$req->isHttps(),
                'httponly' => true,
                'samesite' => 'Strict',
            ]
        );
    }


    // galaxia chat
    if (isset(E::$conf['chat']) && G::$req->method == 'POST' && in_array(G::$req->host, ['/edit/chat/listen', '/edit/chat/publish']))
        require_once __DIR__ . '/GalaxiaEditor/chat/gChat.php';


    // CSRF
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    if (!G::isDevDebug())
        if (G::$req->method == 'POST')
            if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf'])
                geErrorPage(500, 'invalid csrf token.');


    // set editor language
    if (isset(G::$me->options['Language']))
        if (isset(G::$editor->locales[G::$me->options['Language']]))
            G::langSet(G::$me->options['Language']);



    // parse editor configuration
    G::timerStart('editor configuration');

    G::timerStart('gecValidateArray');
    Config::validate();
    G::timerStop('gecValidateArray');


    // get editor slugs from config
    G::$editor->homeSlug = array_key_first(E::$conf) ?? G::$editor->homeSlug;
    foreach (E::$conf as $rootSlug => $confPage) {
        if ($confPage['gcPageType'] == 'gcpImages') {
            G::$editor->imageSlug = $rootSlug;
            break;
        }
    }


    // disable input modifiers(gcInputsWhere, gcInputsWhereCol, gcInputsWhereParent) without perms by setting their type to 'none'
    foreach (E::$conf as $rootSlug => $confPage) {
        foreach ($confPage['gcItem']['gcInputsWhere'] ?? [] as $whereKey => $where) {
            foreach ($where as $whereVal => $inputs) {
                foreach ($inputs as $inputKey => $input) {
                    if (!isset($input['gcPerms'])) continue;
                    if (!array_intersect($input['gcPerms'] ?? [], G::$me->perms)) {
                        E::$conf[$rootSlug]['gcItem']['gcInputsWhere'][$whereKey][$whereVal][$inputKey]['type'] = 'none';
                    }
                }
            }
        }
        foreach ($confPage['gcItem']['gcModules'] ?? [] as $moduleKey => $module) {
            foreach ($module['gcInputsWhereCol'] as $fieldKey => $inputs) {
                foreach ($inputs as $inputKey => $input) {
                    if (!isset($input['gcPerms'])) continue;
                    if (!array_intersect($input['gcPerms'] ?? [], G::$me->perms)) {
                        E::$conf[$rootSlug]['gcItem']['gcModules'][$moduleKey]['gcInputsWhereCol'][$fieldKey][$inputKey]['type'] = 'none';
                    }
                }
            }
            foreach ($module['gcInputsWhereParent'] as $parentKey => $parent) {
                foreach ($parent as $parentVal => $fields) {
                    foreach ($fields as $fieldKey => $inputs) {
                        foreach ($inputs as $inputKey => $input) {
                            if (!isset($input['gcPerms'])) continue;
                            if (!array_intersect($input['gcPerms'] ?? [], G::$me->perms)) {
                                E::$conf[$rootSlug]['gcItem']['gcModules'][$moduleKey]['gcInputsWhereParent'][$parentKey][$parentVal][$fieldKey][$inputKey]['type'] = 'none';
                            }
                        }
                    }
                }
            }
        }
    }


    G::timerStart('arrayRemovePermsRecursive()');
    ArrayShape::removePermsRecursive(E::$conf, G::$me->perms);
    G::timerStop('arrayRemovePermsRecursive()');

    G::timerStart('gecLanguify');
    ArrayShape::languify(E::$conf, array_keys(G::locales()), G::$me->perms);
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
    G::$editor->layout = 'layout-editor';

    $dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {

        $r->get('/edit/{pgSlug:login}', 'redirect-home');
        $r->post('/edit/{pgSlug:login}', 'redirect-home');
        $r->get('/edit/{pgSlug:logout}', 'login/logout');

        if (G::isDev()) {
            $r->get('/edit/{pgSlug:dev}', 'dev/dev');
            $r->get('/edit/{pgSlug:dev}/sitemap', 'dev/sitemap');
            $r->get('/edit/{pgSlug:dev}/urls', 'dev/urls');
            $r->get('/edit/{pgSlug:dev}/cacheDeleteApp', 'dev/cache-delete-app');
            $r->get('/edit/{pgSlug:dev}/cacheDeleteAll', 'dev/cache-delete-all');
            $r->get('/edit/{pgSlug:dev}/info', 'dev/info');
        }
        $r->get('/edit/{pgSlug:dev}/cacheDeleteEditor', 'dev/cache-delete-editor');
        $r->get('/edit/{pgSlug:dev}/imageListDeleteResizes', 'dev/image-list-delete-resizes');
        $r->get('/edit/{pgSlug:dev}/imageListDeleteWebp', 'dev/image-list-delete-webp');
        $r->get('/edit/{pgSlug:dev}/imageListReorder', 'dev/image-list-reorder');

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
                            if (G::isDev()) {
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
                        $r->get('/edit/{pgSlug:' . G::$editor->imageSlug . '}', 'imageList/list');
                        $r->post('/edit/{pgSlug:' . G::$editor->imageSlug . '}', 'imageList/list');

                        if ($confPage['gcImage']['gcDelete'] ?? []) {
                            $r->get('/edit/{pgSlug:' . G::$editor->imageSlug . '}/delete', 'imageList/deleteMulti');
                            $r->post('/edit/{pgSlug:' . G::$editor->imageSlug . '}/delete', 'imageList/deleteMulti-post');
                        }

                        $r->get('/edit/{pgSlug:' . G::$editor->imageSlug . '}/verify', 'imageList/verify');

                        $r->get('/edit/{pgSlug:' . G::$editor->imageSlug . '}/{imgSlug}/resize/{imgW}/{imgH}', 'image/resize/resize-request');
                    }

                    if ($confPage['gcImage']) {
                        if ($confPage['gcImage']['gcInsert']) {
                            $r->get('/edit/{pgSlug:' . G::$editor->imageSlug . '}/{imgSlug:new}', 'image/new/new');
                            $r->post('/edit/{pgSlug:' . G::$editor->imageSlug . '}/{imgSlug:new}', 'image/new/new-post');
                        }
                        if ($confPage['gcImage']['gcSelect']) {
                            $r->get('/edit/{pgSlug:' . G::$editor->imageSlug . '}/{imgSlug}', 'image/image');
                        }
                        if ($confPage['gcImage']['gcUpdate']) {
                            $r->post('/edit/{pgSlug:' . G::$editor->imageSlug . '}/{imgSlug}', 'image/image-post');
                            $r->get('/edit/{pgSlug:' . G::$editor->imageSlug . '}/{imgSlug}/{action:deleteResizes}', 'image/image-delete-resizes-post');
                            $r->get('/edit/{pgSlug:' . G::$editor->imageSlug . '}/{imgSlug}/{action:deleteWebp}', 'image/image-delete-webp-post');
                            $r->get('/edit/{pgSlug:' . G::$editor->imageSlug . '}/{imgSlug}/{action:replace}', 'image/replace/replace');
                            $r->post('/edit/{pgSlug:' . G::$editor->imageSlug . '}/{imgSlug}/{action:replace}', 'image/replace/replace-post');
                            $r->get('/edit/{pgSlug:' . G::$editor->imageSlug . '}/{imgSlug}/{action:resize}', 'image/resize/resize');
                            $r->post('/edit/{pgSlug:' . G::$editor->imageSlug . '}/{imgSlug}/{action:resize}', 'image/resize/resize-post');
                        }
                        if ($confPage['gcImage']['gcDelete']) {
                            $r->get('/edit/{pgSlug:' . G::$editor->imageSlug . '}/{imgSlug}/{action:delete}', 'image/delete/delete');
                            $r->post('/edit/{pgSlug:' . G::$editor->imageSlug . '}/{imgSlug}/{action:delete}', 'image/delete/delete-post');
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

    $routeInfo = $dispatcher->dispatch(G::$req->method, G::$req->pathOriginal);

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

            G::$editor->logic = G::$editor->view = $routeInfo[1];
            break;
    }

} else {

    G::$editor->layout = 'layout-default';
    $dispatcher        = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {

        $r->get('/edit/{pgSlug:login}', 'login/login');
        $r->post('/edit/{pgSlug:login}', 'login/login-post');
        $r->get('/edit/{pgSlug:logout}', 'login/logout');

    });

    $routeInfo = $dispatcher->dispatch(G::$req->method, G::$req->path);

    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            G::errorPage(404, __FILE__ . ':' . __LINE__);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            G::errorPage(403, __FILE__ . ':' . __LINE__);
            break;
        case FastRoute\Dispatcher::FOUND:
            extract($routeInfo[2]); // make php $variables with names and values defined in the routing above.
            E::$pgSlug        = $routeInfo[2]['pgSlug'];
            G::$editor->logic = G::$editor->view = $routeInfo[1];
            break;
    }

}




// custom redirects

if ($routeInfo[1] == 'redirect-home') G::redirect('edit/' . G::$editor->homeSlug, 303);




// Include the logic part of the template.
// - logic can modify which layout and template are going to be used

G::timerStart('logic');
$logicExploded  = explode('/', G::$editor->logic);
$logicPathCount = count($logicExploded);
$logicPath      = '';
if (file_exists(G::$editor->dirLogic . '_autorun.php')) {
    include G::$editor->dirLogic . '_autorun.php';
}
for ($i = 0; $i < $logicPathCount - 1; $i++) {
    $logicPath .= ($logicExploded[$i] . '/');
    if (file_exists(G::$editor->dirLogic . $logicPath . '_autorun.php')) {
        include G::$editor->dirLogic . $logicPath . '_autorun.php';
    }
}
if (file_exists(G::$editor->dirLogic . G::$editor->logic . '.php')) {
    include G::$editor->dirLogic . G::$editor->logic . '.php';
}
G::timerStop('logic');




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
G::timerStart('layout');
include G::$editor->dirLayout . G::$editor->layout . '.phtml';
G::timerStop('layout');

if (G::isDev() && G::$editor->layout != 'none') {
    G::timerPrint(true, true);
}


exit();
