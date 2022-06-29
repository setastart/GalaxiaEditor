<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
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

    private string $tableName;

    function __construct($tableName) {
        $this->tableName = Text::q($tableName . 'Session');
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($sessionId): string {
        $sessionData = '';

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

        return $sessionData ?? '';
    }

    public function write($sessionId, $sessionData): bool {
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
        $stmt->close();

        if (isset($_SESSION['id'])) {
            $stmt = G::prepare("
                UPDATE $this->tableName
                SET _geUserId = ?
                WHERE _geUserSessionId = ?
            ");
            $stmt->bind_param('ds', $_SESSION['id'], $sessionId);
            $stmt->execute();
            $stmt->close();
        }

        return $success;
    }

    public function destroy($sessionId): bool {
        $stmt = G::prepare("
            DELETE FROM $this->tableName
            WHERE _geUserSessionId = ?
        ");

        $stmt->bind_param('s', $sessionId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function gc($maxlifetime): int|false {
        $stmt = G::prepare("
            DELETE FROM $this->tableName
            WHERE
                timestampModified < NOW() - INTERVAL 6 MONTH OR
                timestampCreated < NOW() - INTERVAL 1 YEAR
            ");

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

}
