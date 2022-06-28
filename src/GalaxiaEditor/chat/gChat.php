<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace GalaxiaEditor\chat;

use Galaxia\G;
use Galaxia\RedisCli;


session_write_close();




// process post json

$postJson = file_get_contents('php://input');
if ($postJson === false) Chat::exitArrayToJson(['status' => 'error', 'error' => 'invalid request']);

Chat::$post = json_decode($postJson, true);
if (Chat::$post === null) Chat::exitArrayToJson(['status' => 'error', 'error' => 'invalid json']);




// csrf

if (!isset($_SESSION['csrf']) || !isset(Chat::$post['csrf']) || Chat::$post['csrf'] !== $_SESSION['csrf'])
    Chat::exitArrayToJson(['status' => 'error', 'error' => 'invalid csrf token']);




// redis

Chat::$redis = new RedisCli('localhost', '6379', true);
Chat::$redis->set_error_function(function($error) {
    Chat::exitArrayToJson(['status' => 'error', 'error' => $error]);
});
if (Chat::$redis->cmd('PING')->get() != 'PONG')
    Chat::exitArrayToJson(['status' => 'error', 'error' => 'redis not connected']);




// save my username

Chat::$redis->cmd('HSET', G::$app->mysqlDb . ':userNames', G::$me->id, G::$me->name)->set();




// save last seen online for knowing when user has left

Chat::$redis->cmd('HSET', G::$app->mysqlDb . ':usersLastSeen', G::$me->id, substr(microtime(true) * 1000, 0, 13))->set();




// routing

switch (G::$req->path) {
    case '/edit/chat/listen':
        require __DIR__ . '/gChatListener.php';
        break;

    case '/edit/chat/publish':
        require __DIR__ . '/gChatPublisher.php';
        break;

    default:
        Chat::exitArrayToJson(['status' => 'error', 'error' => 'invalid url']);
        break;
}

exit();
