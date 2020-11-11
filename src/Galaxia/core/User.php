<?php
/* Copyright 2017-2020 Ino Detelić

 - Licensed under the EUPL, Version 1.2 only (the "Licence");
 - You may not use this work except in compliance with the Licence.

 - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

 - Unless required by applicable law or agreed to in writing, software distributed
   under the Licence is distributed on an "AS IS" basis,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 - See the Licence for the specific language governing permissions and limitations under the Licence.
*/

namespace Galaxia;


class User {

    private $tableName;

    public $id             = null;
    public $loggedIn       = false;
    public $email          = '';
    public $name           = '';
    public $timeLastOnline = '';
    public $timeCreated    = '';

    public $perms   = [];
    public $options = [];


    function __construct(string $tableName) {
        $this->tableName = $tableName;
    }


    public function logInFromCookieSessionId($cookieName) {
        if (!isset($_COOKIE[$cookieName])) return;
        Director::timerStart('Session');

        session_name($cookieName);
        if (ip2long($_SERVER['HTTP_HOST'])) {
            session_set_cookie_params(
                31536000,  // 31536000 seconds = 1 year
                '/; SameSite=Strict',
                $_SERVER['HTTP_HOST'],
                false,
                true
            );
        } else {
            session_set_cookie_params(
                31536000,
                '/; SameSite=Strict',
                '.' . $_SERVER['SERVER_NAME'],
                isset($_SERVER['HTTPS']),
                true
            );
        }
        session_set_save_handler(new Session('_geUser'), true);
        session_register_shutdown();

        if (session_start()) $this->loginFromSessionId();

        Director::timerStop('Session');
    }


    public function loginFromSessionId() {
        if (!isset($_SESSION['id'])) return false;

        $this->id = $_SESSION['id'];

        if ($this->load()) {
            return true;
        } else {
            $this->id = null;
        }

        return false;
    }


    public function loadWithId(int $userId) {
        if (!$userId) return false;

        $this->id = $userId;

        if ($this->load()) {
            return true;
        } else {
            $this->id = null;
        }

        return false;
    }


    private function load() {
        $userId         = '';
        $name           = '';
        $email          = '';
        $perms          = '';
        $permsArr       = [];
        $timeLastOnline = '';
        $timeCreated    = '';

        $db   = Director::getMysqli();
        $stmt = $db->prepare("
            SELECT
                _geUserId,
                name,
                email,
                perms,
                UNIX_TIMESTAMP(timestampCreated),
                UNIX_TIMESTAMP(timestampLastOnline)
            FROM $this->tableName
            WHERE _geUserId = ?
        ");
        $stmt->bind_param('d', $this->id);
        $stmt->bind_result($userId, $name, $email, $perms, $timeLastOnline, $timeCreated);
        $stmt->execute();
        $return = $stmt->fetch();
        $stmt->close();

        if ($perms) $permsArr = explode(',', $perms);

        if ($return) {
            $this->loggedIn       = true;
            $this->id             = $userId;
            $this->name           = $name;
            $this->email          = $email;
            $this->perms          = $permsArr;
            $this->timeLastOnline = $timeLastOnline;
            $this->timeCreated    = $timeCreated;

            $stmt = $db->prepare("
                UPDATE $this->tableName
                SET timestampLastOnline = NOW()
                WHERE _geUserId = ?
            ");
            $stmt->bind_param('d', $userId);
            $stmt->execute();
            $stmt->close();

            $this->loadOptions();
        }

        return $return;
    }


    public function setName($name) {
        if (!$this->loggedIn) return false;

        $db   = Director::getMysqli();
        $stmt = $db->prepare("
            UPDATE $this->tableName
            SET name = ?
            WHERE _geUserId = ?
        ");
        $stmt->bind_param('ss', $name, $this->id);
        $stmt->execute();
        $stmt->close();
    }


    public function loadOptions() {
        if (!$this->loggedIn) return false;

        $optionName       = '';
        $optionValue      = '';
        $optionsTableName = $this->tableName . 'Option';

        $db   = Director::getMysqli();
        $stmt = $db->prepare("
            SELECT
                fieldKey,
                value
            FROM $optionsTableName
            WHERE _geUserId = ?
        ");
        $stmt->bind_param('d', $this->id);
        $stmt->bind_result($optionName, $optionValue);
        $stmt->execute();

        while ($stmt->fetch()) {
            $this->options[$optionName] = $optionValue;
        }
        $stmt->close();
    }


    public function setOptions($name, $value) {
        if (!$this->loggedIn) return false;

        if (!array_key_exists($name, $this->options)) {
            echo 'error: setOptions(name)';
            exit();
        }

        $optionsTableName = $this->tableName . 'Option';

        $db   = Director::getMysqli();
        $stmt = $db->prepare("
            INSERT INTO $optionsTableName (
                _geUserId,
                fieldKey,
                value
            )
            VALUES (?, ?, ?)
            ON DUPLICATE fieldKey UPDATE value = ?
        ");
        $stmt->bind_param('dsss', $this->id, $name, $value, $value);
        $stmt->execute();
        $stmt->close();
    }


    public function hasPerm(string $perm) {
        return in_array($perm, $this->perms);
    }

}
