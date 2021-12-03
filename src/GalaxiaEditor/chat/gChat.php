<?php

use Galaxia\G;
use Galaxia\RedisCli;
use GalaxiaEditor\chat\Chat;


session_write_close();


const TIMEOUT_XREAD         = 1000;    // miliseconds
const TIMEOUT_LISTEN        = 11;      // seconds
const TIMEOUT_ALIVE         = 15;      // seconds
const TIMEOUT_LEAVE         = 2;       // seconds
const TIMEOUT_ROOM_INACTIVE = 60 * 30; // seconds




// process post json

$postJson = file_get_contents('php://input');
if ($postJson === false) Chat::exitArrayToJson(['status' => 'error', 'error' => 'invalid request']);

$post = json_decode($postJson, true);
if ($post === null) Chat::exitArrayToJson(['status' => 'error', 'error' => 'invalid json']);

$r = [
    'status' => 'ok',
];




// csrf

if (!isset($_SESSION['csrf']) || !isset($post['csrf']) || $post['csrf'] !== $_SESSION['csrf'])
    Chat::exitArrayToJson(['status' => 'error', 'error' => 'invalid csrf token']);




// redis

$redis = new RedisCli('localhost', '6379', true);
$redis->set_error_function(function($error) {
    Chat::exitArrayToJson(['status' => 'error', 'error' => $error]);
});
if ($redis->cmd('PING')->get() != 'PONG')
    Chat::exitArrayToJson(['status' => 'error', 'error' => 'redis not connected']);




// save my username

$redis->cmd('HSET', $app->mysqlDb . ':userNames', $me->id, $me->name)->set();




// save last seen online for knowing when user has left

$redis->cmd('HSET', $app->mysqlDb . ':usersLastSeen', $me->id, substr(microtime(true) * 1000, 0, 13))->set();




// routing

switch (G::$req->host) {
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




