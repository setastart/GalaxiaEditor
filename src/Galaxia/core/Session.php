<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;

use SessionHandlerInterface;

/*
info from http://php.net/manual/en/function.session-set-save-handler.php

Colin 08-Mar-2007 09:25
Aspects of this have been posted in various comments, but it's helpful to make it clearer.

The custom session handler seems to perform actions in these orders:

When session_start() is called:
    open
    read
    clean (if cleaning is being done this call)
    write
    close

When session_destroy() is called after session_start():
    open
    read
    clean (if cleaning is being done this call)
    destroy
    close

When session_regenerate_id(1) is called after session_start():
    open
    read
    clean (if cleaning is being done this call)
    destroy
    write
    close
*/


class Session implements SessionHandlerInterface {

    static public bool $debug = false;
    static public bool $redis = false;

    const int TIMEOUT = 60;

    private string $tableName;
    private string $sessionDataOnRead = '';

    function __construct($tableName) {
        $this->tableName = Text::q($tableName . 'Session');
    }

    static function prefix(string $sessionId): string {
        return G::$app->mysqlDb . ':session:' . $sessionId;
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($sessionId): string {
        $sessionData = '';


        if (Session::$redis) {
            AppTimer::start('Session read redis');
            if ($sessionData = G::redis()?->cmd('GET', Session::prefix($sessionId))->get()) {
                AppTimer::stop('Session read redis');
            } else {
                AppTimer::stop('Session read redis', rename: 'Session read redis failed');
            }
        }

        if (!$sessionData) {

            if (Session::$debug) AppTimer::start('Session read mysql');
            $stmt = G::prepare("
                SELECT sessionData
                FROM $this->tableName
                WHERE
                    _geUserSessionId = ? AND
                    timestampCreated > NOW() - INTERVAL 1 YEAR AND
                    timestampModified > NOW() - INTERVAL 6 MONTH
            ");

            $stmt->bind_param('s', $sessionId);
            $stmt->bind_result($sessionData);
            $stmt->execute();
            $stmt->fetch();
            $stmt->close();
            if (Session::$debug) AppTimer::stop('Session read mysql');

            if (Session::$redis && $sessionData) {
                if (Session::$debug) AppTimer::start('Session write redis from mysql');
                if (G::redis()?->cmd('SETEX', Session::prefix($sessionId), Session::TIMEOUT, $sessionData)->set()) {
                    if (Session::$debug) AppTimer::stop('Session write redis from mysql');
                } else {
                    if (Session::$debug) AppTimer::stop('Session write redis from mysql', rename: 'Session write redis from mysql failed');
                }
            }
        }

        $this->sessionDataOnRead = $sessionData ?? '';

        return $sessionData ?? '';
    }

    public function write($sessionId, $sessionData): bool {

        if ($sessionData === $this->sessionDataOnRead) return true;


        if (Session::$redis) {
            AppTimer::start('Session write redis');
            if (G::redis()?->cmd('SETEX', Session::prefix($sessionId), Session::TIMEOUT, $sessionData)->set()) {
                AppTimer::stop('Session write redis');
            } else {
                AppTimer::stop('Session write redis', rename: 'Session write redis failed');
            }
        }

        if (Session::$debug) AppTimer::start('Session write mysql');
        $stmt = G::prepare("
            INSERT INTO $this->tableName (
                _geUserSessionId,
                sessionData,
                timestampCreated,
                timestampModified
            )
            VALUES (?, ?, NOW(), NOW())
            ON DUPLICATE KEY
            UPDATE
                _geUserSessionId = ?,
                sessionData = ?,
                timestampModified = NOW()
        ");

        $stmt->bind_param('ssss', $sessionId, $sessionData, $sessionId, $sessionData);
        $success = $stmt->execute();
        if (Session::$debug) AppTimer::stop('Session write mysql', 'Session write mysql' . $stmt->insert_id ? 'insert' : 'update');
        $stmt->close();

        if (isset($_SESSION['id'])) {
            if (Session::$debug) AppTimer::start('Session write mysql update userid');
            $stmt = G::prepare("
                UPDATE $this->tableName
                SET _geUserId = ?
                WHERE _geUserSessionId = ?
            ");
            $stmt->bind_param('ds', $_SESSION['id'], $sessionId);
            $stmt->execute();
            $stmt->close();
            if (Session::$debug) AppTimer::stop('Session write mysql update userid');
        }

        return $success;
    }

    public function destroy($sessionId): bool {
        if (Session::$debug) AppTimer::start('Session delete mysql');
        $stmt = G::prepare("
            DELETE FROM $this->tableName
            WHERE _geUserSessionId = ?
        ");

        $stmt->bind_param('s', $sessionId);
        $success = $stmt->execute();
        $stmt->close();
        if (Session::$debug) AppTimer::stop('Session delete mysql');

        if (Session::$redis) {
            if (Session::$debug) AppTimer::start('Session delete redis');
            G::redis()?->cmd('DEL', Session::prefix($sessionId))->set();
            if (Session::$debug) AppTimer::stop('Session delete redis');
        }

        return $success;
    }

    public function gc($maxlifetime): int|false {
        if (Session::$debug) AppTimer::start('Session garbage collection mysql');
        $stmt = G::prepare("
            DELETE FROM $this->tableName
            WHERE
                timestampModified < NOW() - INTERVAL 6 MONTH OR
                timestampCreated < NOW() - INTERVAL 1 YEAR
            ");

        $success = $stmt->execute();
        $stmt->close();
        if (Session::$debug) AppTimer::stop('Session garbage collection mysql');

        return $success;
    }

}
