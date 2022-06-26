<?php

use Galaxia\FastRoute\Dispatcher;
use Galaxia\FastRoute\RouteCollector;
use Galaxia\G;
use GalaxiaEditor\E;
use function Galaxia\FastRoute\simpleDispatcher;


G::$editor->layout = 'layout-logged-out';

$dispatcher        = simpleDispatcher(function(RouteCollector $r) {
    $r->get('/edit/{pgSlug:login}', 'login/login');
    $r->post('/edit/{pgSlug:login}', 'login/login-post');
    $r->get('/edit/{pgSlug:logout}', 'login/logout');
});

$routeInfo = $dispatcher->dispatch(G::$req->method, G::$req->path);

switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        G::errorPage(404, __FILE__ . ':' . __LINE__);
        break;

    case Dispatcher::METHOD_NOT_ALLOWED:
        G::errorPage(403, __FILE__ . ':' . __LINE__);
        break;

    case Dispatcher::FOUND:
        E::$pgSlug        = $routeInfo[2]['pgSlug'];
        G::$editor->logic = G::$editor->view = $routeInfo[1];
        break;
}
