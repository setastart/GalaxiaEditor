<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace GalaxiaEditor\chat;

use Galaxia\G;
use Galaxia\Text;
use GalaxiaEditor\E;


//  Listen to activity in room
//
//  When a client (browser tab) loads or reloads a chat page, a 'listen request' is sent to the 'listener' (this file)
//  The 'listen request' contains a list of rooms, and for each room:
//      - the last message id from that room
//      - the users the client knows are in the room
//
//  The listener listens to many rooms at the same time.
//
//  On upon receiving a 'listen request', the 'listener' does:
//      1. Enter rooms
//      2. See who's in each room
//      3. Listen to new messages in the rooms for a short period of time
//      4. See if anyone entered or left any of the rooms
//      5. Repeat steps 3-4 for until a message is received, someone entered or left the room or some time has passed
//      6. Send a 'listen response' to the client with new messages, the users and the last message ids for each room


$r = [
    'status'   => 'ok',
    'error'    => '',
    'clientId' => Chat::$post['clientId'],

    'users' => [
    ],

    'rooms' => [
    ],
];




// validation

if (!isset(Chat::$post['clientId'])) Chat::exitArrayToJson(['status' => 'error', 'error' => 'missing clientId']);
if (!isset(Chat::$post['rooms'])) Chat::exitArrayToJson(['status' => 'error', 'error' => 'missing rooms']);
if (empty(Chat::$post['rooms'])) Chat::exitArrayToJson(['status' => 'error', 'error' => 'rooms empty']);




// Enter rooms

$roomsPrefixed = [];
$clientId      = Chat::$post['clientId'];

foreach (Chat::$post['rooms'] ?? [] as $room => $roomData) {
    G::redis()->cmd('SET', G::$app->mysqlDb . ':editing:' . $room . ':' . $clientId, G::$me->id, 'EX', Chat::timeoutAlive)->set();
    $roomsPrefixed[G::$app->mysqlDb . ':rooms:' . $room] = $roomData['lastId'];

    if (!G::redis()->cmd('EXISTS', G::$app->mysqlDb . ':rooms:' . $room)->get())
        G::redis()->cmd('XADD', G::$app->mysqlDb . ':rooms:' . $room, '*', 'user', G::$me->id, 'create', Text::t('Room Created'))->set();

    if ($room != '/edit/chat')
        G::redis()->cmd('EXPIRE', G::$app->mysqlDb . ':rooms:' . $room, Chat::timeoutRoomInactive)->set();

}
// $r['rooms'] = Chat::$post['rooms'];




// listen

$messageActivity = false;
$userActivity    = false;
$timeoutListen   = time() + Chat::timeoutListen;
$usersSeen       = [];

while (!$messageActivity && !$userActivity && time() < $timeoutListen) {

    // listen to new messages

    $xread = G::redis()->cmd('XREAD', 'BLOCK', Chat::timeoutXread, 'COUNT', 100, 'STREAMS', ...array_keys($roomsPrefixed), ...array_values($roomsPrefixed))->get();
    if ($xread) $messageActivity = true;


    // get users in rooms

    foreach (Chat::$post['rooms'] as $room => $roomData) {
        $clients = G::redis()->cmd('KEYS', G::$app->mysqlDb . ':editing:' . $room . ':*')->get();

        unset($r['rooms'][$room]['users']);

        foreach ($clients as $client) {
            $userId = G::redis()->cmd('GET', $client)->get();
            if (!$userId) continue;
            if (!isset($usersSeen[$userId])) $usersSeen[$userId] = true;

            if ($userId == G::$me->id) {
                $r['rooms'][$room]['users'][$userId] = ($r['rooms'][$room]['users'][$userId] ?? 0) + 1;
            } else {
                $r['rooms'][$room]['users'][$userId] = 1;
            }
        }
        if (isset($r['rooms'][$room]['users']) && $r['rooms'][$room]['users'] != Chat::$post['rooms'][$room]['users']) $userActivity = true;
    }
}




// get usernames and last online timestamp

$temp = G::redis()->cmd('HSCAN', G::$app->mysqlDb . ':userNames', '0')->get();
for ($i = 0; $i < count($temp[1]); $i += 2) {
    $r['users'][$temp[1][$i]]['name'] = $temp[1][$i + 1];
}

$temp = G::redis()->cmd('HSCAN', G::$app->mysqlDb . ':usersLastSeen', '0')->get();
for ($i = 0; $i < count($temp[1]); $i += 2) {
    $r['users'][$temp[1][$i]]['lastSeen'] = $temp[1][$i + 1];
}

$r['users'][0] = [
    'name'     => E::$conf['chat']['gcMenuTitle'] ?? 'Galaxia Chat',
    'lastSeen' => '0',
];




// build messages

$prefixLength = strlen(G::$app->mysqlDb . ':rooms:');
foreach ($xread ?? [] as $streams) {
    $room = substr($streams[0], $prefixLength);

    foreach ($streams[1] as $message) {
        if (!in_array($message[1][2], ['speak', 'create'])) continue;
        $r['rooms'][$room]['messages'][] = [
            'user'      => $message[1][1],
            'type'      => $message[1][2],
            'content'   => $message[1][3],
            'timestamp' => substr($message[0], 0, 13),
        ];
    }
    $r['rooms'][$room]['lastId'] = $message[0] ?? '0';
}




Chat::exitArrayToJson($r);
