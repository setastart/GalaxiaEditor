<?php

use Galaxia\G;
use Galaxia\Text;
use GalaxiaEditor\chat\Chat;


//  Process messages sent from the clients (browser tabs).
//
//  The client sends a 'publish request' when:
//      - The user wants to send a message to a specific room. - 'speak' message type
//      - The user navigates to a different page.              - 'leave' message type
//      - The user reloads the page.                           - 'leave' message type
//      - The user closes the tab.                             - 'leave' message type
//
//  A 'speak' message adds a message to the room.
//
//  A 'leave' message does not add a message to the room.
//      We don't leave the room immediately because the tab could be reloading.
//      If tab was reloading, 'listener' will reenter the room and extend this time.


$r = [
    'status' => 'error',
    'error' => '',
    'messages' => [],
];

$msg      = $post['msg'];
$clientId = $post['clientId'];
switch ($post['type']) {
    case 'speak':
        $room = $post['room'];
        // send message to room
        if ($redis->cmd('XADD', G::$app->mysqlDb . ':rooms:' . $room, '*', 'user', G::$me->id, 'speak', Text::h(trim($msg)))->set()) {
            $redis->cmd('SET', G::$app->mysqlDb . ':editing:' . $room . ':' . $clientId, G::$me->id, 'EX', TIMEOUT_ALIVE)->set();
            Chat::exitArrayToJson(['status' => 'ok']);
        } else {
            Chat::exitArrayToJson(['status' => 'error', 'error' => 'could not store message']);
        } break;

    case 'leave':
        // leave rooms in X seconds
        foreach ($post['rooms'] as $roomToLeave) {
            $redis->cmd('EXPIRE', G::$app->mysqlDb . ':editing:' . $roomToLeave . ':' . $clientId, TIMEOUT_LEAVE)->set();
        }
        // $redis->cmd('SET', G::$app->mysqlDb . ':leaving:' . $roomToLeave . ':' . $clientId, G::$me->id, 'EX', TIMEOUT_ALIVE)->set();
        Chat::exitArrayToJson(['status' => 'ok', 'type' => 'leaving ' . $clientId]);
        break;

    default:
        Chat::exitArrayToJson(['status' => 'error', 'error' => 'invalid message type']);
        break;
}



Chat::exitArrayToJson(['status' => 'error', 'error' => 'invalid message', 'post' => $post]);

exit();
