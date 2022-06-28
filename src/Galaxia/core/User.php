<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


class User {

    private string $tableName;

    public ?int    $id             = null;
    public bool    $loggedIn       = false;
    public string  $email          = '';
    public string  $name           = '';
    public string  $timeLastOnline = '';
    public string  $timeCreated    = '';

    public array $perms   = [];
    public array $options = [];


    function __construct(string $tableName = '_geUser') {
        $this->tableName = $tableName;
    }


    public function logInFromCookieSessionId(string $cookieName): void {
        if (!isset($_COOKIE[$cookieName])) return;
        G::timerStart('Session');
        session_name($cookieName);
        session_set_cookie_params([
            'lifetime' => 31536000,
            'path'     => '/',
            'secure'   => G::$req->isHttps(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_set_save_handler(new Session('_geUser'), true);
        session_register_shutdown();

        if (session_start()) $this->loginFromSessionId();

        G::timerStop('Session');
    }


    public function loginFromSessionId(): bool {
        if (!isset($_SESSION['id'])) return false;

        $this->id = $_SESSION['id'];

        if ($this->load()) {
            return true;
        } else {
            $this->id = null;
        }

        return false;
    }


    public function loadWithId(int $userId): bool {
        if (!$userId) return false;

        $this->id = $userId;

        if ($this->load()) {
            return true;
        } else {
            $this->id = null;
        }

        return false;
    }


    private function load(): bool {
        $userId         = '';
        $name           = '';
        $email          = '';
        $perms          = '';
        $permsArr       = [];
        $timeLastOnline = '';
        $timeCreated    = '';

        $stmt = G::prepare("
            SELECT
                _geUserId,
                name,
                email,
                perms,
                UNIX_TIMESTAMP(timestampLastOnline),
                UNIX_TIMESTAMP(timestampCreated)
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
            $this->timeLastOnline = $timeLastOnline ?? '';
            $this->timeCreated    = $timeCreated;

            $stmt = G::prepare("
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


    public function setName($name): void {
        if (!$this->loggedIn) return;

        $stmt = G::prepare("
            UPDATE $this->tableName
            SET name = ?
            WHERE _geUserId = ?
        ");
        $stmt->bind_param('ss', $name, $this->id);
        $stmt->execute();
        $stmt->close();
    }


    public function loadOptions(): void {
        if (!$this->loggedIn) return;

        $optionName       = '';
        $optionValue      = '';
        $optionsTableName = $this->tableName . 'Option';

        $stmt = G::prepare("
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


    public function setOptions($name, $value): void {
        if (!$this->loggedIn) return;

        if (!array_key_exists($name, $this->options)) {
            echo 'error: setOptions(name)';
            exit();
        }

        $optionsTableName = $this->tableName . 'Option';

        $stmt = G::prepare("
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


    public function hasPerm(string $perm): bool {
        return in_array($perm, $this->perms);
    }

}
