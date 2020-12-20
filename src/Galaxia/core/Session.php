<?php
/* Copyright 2017-2020 Ino Detelić & Zaloa G. Ramos

 - Licensed under the EUPL, Version 1.2 only (the "Licence");
 - You may not use this work except in compliance with the Licence.

 - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

 - Unless required by applicable law or agreed to in writing, software distributed
   under the Licence is distributed on an "AS IS" basis,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 - See the Licence for the specific language governing permissions and limitations under the Licence.
*/

/* info from http://php.net/manual/en/function.session-set-save-handler.php

Colin 08-Mar-2007 09:25
Aspects of this have been posted in various comments but it's helpful to make it clearer.

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

namespace Galaxia;


use SessionHandlerInterface;


class Session implements SessionHandlerInterface {

    private string $tableName;

    function __construct($tableName) {
        $this->tableName = $tableName . 'Session';
    }

    public function open($savePath, $sessionName) {
        return true;
    }

    public function close() {
        return true;
    }

    public function read($sessionId) {
        $sessionData = '';

        $db = Director::getMysqli();

        $stmt = $db->prepare("
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

    public function write($sessionId, $sessionData) {
        $sessionId = filter_var($sessionId, FILTER_SANITIZE_STRING);

        $db = Director::getMysqli();

        $stmt = $db->prepare("
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
            $stmt = $db->prepare("
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

    public function destroy($sessionId) {
        $db = Director::getMysqli();

        $stmt = $db->prepare("
            DELETE FROM $this->tableName
            WHERE _geUserSessionId = ?
        ");

        $stmt->bind_param('s', $sessionId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function gc($maxlifetime) {
        $db = Director::getMysqli();

        $stmt = $db->prepare("
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
