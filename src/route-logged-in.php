<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

use Galaxia\FastRoute\Dispatcher;
use Galaxia\FastRoute\RouteCollector;
use Galaxia\G;
use GalaxiaEditor\Cache;
use GalaxiaEditor\config\Config;
use GalaxiaEditor\config\ConfigDb;
use GalaxiaEditor\E;
use function Galaxia\FastRoute\cachedDispatcher;



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


// cross-site request forgery (CSRF) protection
if (!isset($_SESSION['csrf'])) {
    try {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        G::errorPage(500, 'random_bytes() given inappropriate source of randomness.');
    }
}
if (!G::isDevDebug() && G::$req->method == 'POST') {
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
        G::errorPage(500, 'invalid csrf token.');
    }
}


// set editor language
if (isset(G::$me->options['Language'])) {
    if (isset(G::$editor->locales[G::$me->options['Language']])) {
        G::langSet(G::$me->options['Language']);
    }
}



// editor configuration

E::$conf = Cache::config(fn() => Config::load());

if (G::isDevDebug()) {
    G::timerStart('Database validation');
    ConfigDb::validate();
    G::timerStop('Database validation');
}

Config::loadSlugs();



// galaxia chat
if (isset(E::$conf['chat']) && G::$req->method == 'POST' && in_array(G::$req->path, ['/edit/chat/listen', '/edit/chat/publish'])) {
    require_once __DIR__ . '/GalaxiaEditor/chat/gChat.php';
}


// routes
G::timerStart('Routing');
G::$editor->layout = 'layout-editor';

$dispatcher = cachedDispatcher(function(RouteCollector $r) {

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
        $r->get('/edit/{pgSlug:dev}/opcache', 'dev/opcache');
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

}, [
    'cacheFile'     => Cache::route(),
    'cacheDisabled' => G::$req->cacheBypass,
]);


G::timerStop('Routing');

$routeInfo = $dispatcher->dispatch(G::$req->method, G::$req->pathOriginal);

switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        G::errorPage(404, __FILE__ . ':' . __LINE__);
        break;

    case Dispatcher::METHOD_NOT_ALLOWED:
        G::errorPage(403, __FILE__ . ':' . __LINE__);
        break;

    case Dispatcher::FOUND:
        if ($routeInfo[1] == 'redirect-home') {
            G::redirect('edit/' . G::$editor->homeSlug, 303);
        }

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
