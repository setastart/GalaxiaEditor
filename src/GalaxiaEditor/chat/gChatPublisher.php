<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace GalaxiaEditor\chat;

use Galaxia\G;
use Galaxia\Text;


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


$msg      = Chat::$post['msg'];
$clientId = Chat::$post['clientId'];
switch (Chat::$post['type']) {
    case 'speak':
        $room = Chat::$post['room'];
        // send message to room
        if (G::redis()?->cmd('XADD', G::$app->mysqlDb . ':rooms:' . $room, '*', 'user', G::$me->id, 'speak', Text::h(trim($msg)))->set()) {
            G::redis()?->cmd('SET', G::$app->mysqlDb . ':editing:' . $room . ':' . $clientId, G::$me->id, 'EX', Chat::timeoutAlive)->set();
            Chat::exitArrayToJson(['status' => 'ok']);
        } else {
            Chat::exitArrayToJson(['status' => 'error', 'error' => 'could not store message']);
        }

    case 'leave':
        // leave rooms in X seconds
        foreach (Chat::$post['rooms'] as $roomToLeave) {
            G::redis()?->cmd('EXPIRE', G::$app->mysqlDb . ':editing:' . $roomToLeave . ':' . $clientId, Chat::timeoutLeave)->set();
        }

        // G::redis()?->cmd('SET', G::$app->mysqlDb . ':leaving:' . $roomToLeave . ':' . $clientId, G::$me->id, 'EX', TIMEOUT_ALIVE)->set();
        Chat::exitArrayToJson(['status' => 'ok', 'type' => 'leaving ' . $clientId]);

    default:
        Chat::exitArrayToJson(['status' => 'error', 'error' => 'invalid message type']);
}
