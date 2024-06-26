<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace GalaxiaEditor\chat;


class Chat {

    public const int timeoutXread        = 1000;    // milliseconds
    public const int timeoutListen       = 11;      // seconds
    public const int timeoutAlive        = 15;      // seconds
    public const int timeoutLeave        = 2;       // seconds
    public const int timeoutRoomInactive = 60 * 30; // seconds

    public static mixed $post;

    static function exitArrayToJson($r): never {
        header('Content-Type: application/json');
        exit(json_encode($r, JSON_PRETTY_PRINT));
    }

}
