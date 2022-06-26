<?php


namespace GalaxiaEditor\chat;


use Galaxia\RedisCli;

class Chat {

    public const timeoutXread        = 1000;    // milliseconds
    public const timeoutListen       = 11;      // seconds
    public const timeoutAlive        = 15;      // seconds
    public const timeoutLeave        = 2;       // seconds
    public const timeoutRoomInactive = 60 * 30; // seconds

    public static mixed    $post;
    public static RedisCli $redis;

    static function exitArrayToJson($r): never {
        header('Content-Type: application/json');
        exit(json_encode($r, JSON_PRETTY_PRINT));
    }

}
