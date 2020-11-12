<?php


namespace GalaxiaEditor\chat;


class Chat {

    public static function exitArrayToJson($r) {
        header('Content-Type: application/json');
        exit(json_encode($r, JSON_PRETTY_PRINT));
    }

}
