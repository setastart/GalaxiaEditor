<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


use function serialize;
use function unserialize;

class User {

    const TIMEOUT             = 120;
    const TIMEOUT_LAST_ONLINE = 60;

    static public bool $debug = false;
    static public bool $redis = false;

    private string $tableName;

    public ?int   $id             = null;
    public bool   $loggedIn       = false;
    public string $email          = '';
    public string $name           = '';
    public string $timeLastOnline = '';
    public string $timeCreated    = '';

    public array $perms   = [];
    public array $options = [];


    function __construct(string $tableName = '_geUser') {
        $this->tableName = $tableName;
    }


    static function prefix(string $sessionId): string {
        return G::$app->mysqlDb . ':session:' . $sessionId;
    }


    public function logInFromCookieSessionId(string $cookieName): void {
        if (!isset($_COOKIE[$cookieName])) return;
        AppTimer::start('Session');
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
        $sessionStarted = session_start();
        AppTimer::stop('Session');

        if ($sessionStarted) {
            AppTimer::start('Login');
            $this->loginFromSessionId();
            AppTimer::stop('Login');
        }
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
        AppTimer::start(__METHOD__);

        $userId         = '';
        $name           = '';
        $email          = '';
        $perms          = '';
        $permsArr       = [];
        $timeLastOnline = '';
        $timeCreated    = '';

        if (User::$redis) {
            if ($this->redisLoad()) {
                AppTimer::stop(__METHOD__, __METHOD__ . ' redis');
                return true;
            }
        }

        if (User::$debug) AppTimer::start(__METHOD__ . ' mysql');
        $table = Text::q($this->tableName);
        $stmt  = G::prepare("
                SELECT
                    _geUserId,
                    name,
                    email,
                    perms,
                    UNIX_TIMESTAMP(timestampLastOnline),
                    UNIX_TIMESTAMP(timestampCreated)
                FROM $table
                WHERE _geUserId = ?
            ");
        $stmt->bind_param('d', $this->id);
        $stmt->bind_result($userId, $name, $email, $perms, $timeLastOnline, $timeCreated);
        $stmt->execute();
        $return = $stmt->fetch();
        $stmt->close();
        if (User::$debug) AppTimer::stop(__METHOD__ . ' mysql');

        if ($perms) $permsArr = explode(',', $perms);

        if ($return) {
            $this->loggedIn       = true;
            $this->id             = $userId;
            $this->name           = $name;
            $this->email          = $email;
            $this->perms          = $permsArr;
            $this->timeLastOnline = $timeLastOnline ?? '';
            $this->timeCreated    = $timeCreated;

            $this->loadOptions();

            if (User::$redis) $this->redisSave();
            AppTimer::stop(__METHOD__, __METHOD__ . ' mysql');
        } else {
            AppTimer::stop(__METHOD__, __METHOD__ . ' failed');
        }

        return $return;
    }


    public function updateLastOnline(): void {
        if (!$this->loggedIn) return;

        if (User::$debug) AppTimer::start(__METHOD__);

        if (User::$redis && $data = G::redis()?->cmd('GET', G::$app->mysqlDb . ':userLastOnline:' . $this->id)->get()) {
            $this->timeLastOnline = $data;
            if (User::$debug) AppTimer::stop(__METHOD__, __METHOD__ . ' redis read');
            return;
        }

        $this->timeLastOnline = time();

        if (User::$debug) AppTimer::start(__METHOD__ . ' mysql');
        $table = Text::q($this->tableName);
        $stmt  = G::prepare("
            UPDATE $table
            SET timestampLastOnline = FROM_UNIXTIME(?)
            WHERE _geUserId = ?
        ");
        $stmt->bind_param('sd', $this->timeLastOnline, $this->id);
        $stmt->execute();
        $stmt->close();
        if (User::$debug) AppTimer::stop(__METHOD__ . ' mysql');

        if (User::$redis) {
            if (User::$debug) AppTimer::start(__METHOD__ . ' redis write');
            if (G::redis()?->cmd('SETEX', G::$app->mysqlDb . ':userLastOnline:' . $this->id, User::TIMEOUT_LAST_ONLINE, $this->timeLastOnline)->set()) {
                if (User::$debug) AppTimer::stop(__METHOD__ . ' redis write');
            } else {
                if (User::$debug) AppTimer::stop(__METHOD__ . ' redis write', rename: __METHOD__ . ' redis write failed');
            }
        }

        if (User::$debug) AppTimer::stop(__METHOD__, rename: __METHOD__ . ' mysql');
    }

    public function setName($name): void {
        if (!$this->loggedIn) return;

        $table = Text::q($this->tableName);
        $stmt  = G::prepare("
            UPDATE $table
            SET name = ?
            WHERE _geUserId = ?
        ");
        $stmt->bind_param('ss', $name, $this->id);
        $stmt->execute();
        $stmt->close();
    }


    public function loadOptions(): void {
        if (!$this->loggedIn) return;
        if (Session::$debug) AppTimer::start(__METHOD__);

        $optionName   = '';
        $optionValue  = '';
        $optionsTable = Text::q($this->tableName . 'Option');

        $stmt = G::prepare("
            SELECT
                fieldKey,
                value
            FROM $optionsTable
            WHERE _geUserId = ?
        ");
        $stmt->bind_param('d', $this->id);
        $stmt->bind_result($optionName, $optionValue);
        $stmt->execute();

        while ($stmt->fetch()) {
            $this->options[$optionName] = $optionValue;
        }
        if (Session::$debug) AppTimer::stop(__METHOD__);
        $stmt->close();
    }


    public function setOptions($name, $value): void {
        if (!$this->loggedIn) return;

        if (!array_key_exists($name, $this->options)) {
            echo 'error: setOptions(name)';
            exit();
        }

        $optionsTable = Text::q($this->tableName . 'Option');

        $stmt = G::prepare("
            INSERT INTO $optionsTable (
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




    public function redisSave(): void {
        if (User::$debug) AppTimer::start(__METHOD__);
        $data = [
            'loggedIn'       => $this->loggedIn,
            'id'             => $this->id,
            'name'           => $this->name,
            'email'          => $this->email,
            'perms'          => $this->perms,
            'timeLastOnline' => $this->timeLastOnline,
            'timeCreated'    => $this->timeCreated,
            'options'        => $this->options,
        ];

        if (G::redis()?->cmd('SETEX', G::$app->mysqlDb . ':user:' . $this->id, User::TIMEOUT, serialize($data))->set()) {
            if (User::$debug) AppTimer::stop(__METHOD__);
        } else {
            if (User::$debug) AppTimer::stop(__METHOD__, rename: __METHOD__ . ' failed');
        }
    }

    public function redisLoad(): bool {
        if (User::$debug) AppTimer::start(__METHOD__);
        $data = G::redis()?->cmd('GET', G::$app->mysqlDb . ':user:' . $this->id)->get();
        if (!$data) {
            if (User::$debug) AppTimer::stop(__METHOD__, rename: __METHOD__ . ' failed');
            return false;
        }
        $data = unserialize($data);

        $this->loggedIn       = $data['loggedIn'];
        $this->id             = $data['id'];
        $this->name           = $data['name'];
        $this->email          = $data['email'];
        $this->perms          = $data['perms'];
        $this->timeLastOnline = $data['timeLastOnline'];
        $this->timeCreated    = $data['timeCreated'];
        $this->options        = $data['options'];
        if (User::$debug) AppTimer::stop(__METHOD__);
        return true;
    }

}
