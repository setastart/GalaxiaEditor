<?php

use Galaxia\Director;

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
    'clientId' => $post['clientId'],

    'users' => [
    ],

    'rooms' => [
    ],
];




// validation

if (!isset($post['clientId'])) exitArrayToJson(['status' => 'error', 'error' => 'missing clientId']);
if (!isset($post['rooms'])) exitArrayToJson(['status' => 'error', 'error' => 'missing rooms']);
if (empty($post['rooms'])) exitArrayToJson(['status' => 'error', 'error' => 'rooms empty']);




// Enter rooms

$roomsPrefixed = [];
$clientId = $post['clientId'];

foreach ($post['rooms'] ?? [] as $room => $roomData) {
    $redis->cmd('SET', $app->mysqlDb . ':editing:' . $room . ':' . $clientId, $me->id, 'EX', TIMEOUT_ALIVE)->set();
    $roomsprefixed[$app->mysqlDb . ':rooms:' . $room] = $roomData['lastId'];

    if (!$redis->cmd('EXISTS', $app->mysqlDb . ':rooms:' . $room)->get())
        $redis->cmd('XADD', $app->mysqlDb . ':rooms:' . $room, '*', 'user', $me->id, 'create', t('Room Created'))->set();

    if ($room != '/edit/chat')
        $redis->cmd('EXPIRE', $app->mysqlDb . ':rooms:' . $room, TIMEOUT_ROOM_INACTIVE)->set();

}
// $r['rooms'] = $post['rooms'];




// listen

$messageActivity = false;
$userActivity = false;
$timeoutListen = time() + TIMEOUT_LISTEN;
$usersSeen = [];

while (!$messageActivity && !$userActivity && time() < $timeoutListen) {

    // listen to new messages

    $xread = $redis->cmd('XREAD', 'BLOCK', TIMEOUT_XREAD, 'COUNT', 100, 'STREAMS', ...array_keys($roomsprefixed), ...array_values($roomsprefixed))->get();
    if ($xread) $messageActivity = true;


    // get users in rooms

    foreach ($post['rooms'] as $room => $roomData) {
        $clients = $redis->cmd('KEYS', $app->mysqlDb . ':editing:' . $room . ':*')->get();

        unset($r['rooms'][$room]['users']);

        foreach ($clients as $client) {
            $userId = $redis->cmd('GET', $client)->get();
            if (!$userId) continue;
            if (!isset($usersSeen[$userId])) $usersSeen[$userId] = true;

            if ($userId == $me->id) {
                $r['rooms'][$room]['users'][$userId] = ($r['rooms'][$room]['users'][$userId] ?? 0) + 1;
            } else {
                $r['rooms'][$room]['users'][$userId] = 1;
            }
        }
        if (isset($r['rooms'][$room]['users']) && $r['rooms'][$room]['users'] != $post['rooms'][$room]['users']) $userActivity = true;
    }
}




// get user names and last online timestamp

$temp = $redis->cmd('HSCAN', $app->mysqlDb . ':userNames', '0')->get();
for ($i = 0; $i < count($temp[1]); $i += 2) {
    $r['users'][$temp[1][$i]]['name'] = $temp[1][$i + 1];
}

$temp = $redis->cmd('HSCAN', $app->mysqlDb . ':usersLastSeen', '0')->get();
for ($i = 0; $i < count($temp[1]); $i += 2) {
    $r['users'][$temp[1][$i]]['lastSeen'] = $temp[1][$i + 1];
}

$r['users'][0] = [
    'name'     => $geConf['chat']['gcMenuTitle'] ?? 'Galaxia Chat',
    'lastSeen' => '0',
];





// build messages

$prefixLength = strlen($app->mysqlDb . ':rooms:');
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
    $r['rooms'][$room]['lastId'] = $message[0];
}




exitArrayToJson($r);







