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

namespace Galaxia;


class Authentication {

    private string $tblUser;
    private string $tblUserRegisterRequest;
    private string $tblUserOption;
    private string $tblUserPasswordResetRequest;
    private string $tblUserEmailChangeRequest;

    private int $cryptoNrOfBytes = 16;


    function __construct(string $tableNamePrefix = '_geUser') {
        $this->tblUser                     = $tableNamePrefix;
        $this->tblUserOption               = $tableNamePrefix . 'Option';
        $this->tblUserPasswordResetRequest = $tableNamePrefix . 'PasswordResetRequest';
        $this->tblUserRegisterRequest      = $tableNamePrefix . 'RegisterRequest';
        $this->tblUserEmailChangeRequest   = $tableNamePrefix . 'EmailChangeRequest';
    }


    function userAdd($name, $email, $password) {
        if (!$name || !$email || !$password) return false;

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $db   = G::getMysqli();
        $stmt = $db->prepare("
            INSERT INTO $this->tblUser (
                name,
                email,
                passwordHash
            )
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('sss', $name, $email, $passwordHash);
        $stmt->execute();
        $insertedId = $stmt->insert_id;
        $stmt->close();

        return $insertedId;
    }


    function userAuthenticateEmailPassword($email, $password): bool {
        if (!$email || !$password) return false;
        $passwordHash = '';

        $db   = G::getMysqli();
        $stmt = $db->prepare("
            SELECT passwordHash
            FROM $this->tblUser
            WHERE email = ?
        ");
        $stmt->bind_param('s', $email);
        $stmt->bind_result($passwordHash);
        $stmt->execute();
        $result = $stmt->fetch();
        $stmt->close();

        return ($result && password_verify($password, $passwordHash));
    }


    function userAuthenticateIdPassword($userId, $password): bool {
        if (!$userId || !$password) return false;
        $passwordHash = '';

        $db   = G::getMysqli();
        $stmt = $db->prepare("
            SELECT passwordHash
            FROM $this->tblUser
            WHERE _geUserId = ?
        ");
        $stmt->bind_param('d', $userId);
        $stmt->bind_result($passwordHash);
        $stmt->execute();
        $result = $stmt->fetch();
        $stmt->close();

        return ($result && password_verify($password, $passwordHash));
    }


    function userGetIdByEmail($email) {
        if (!$email) return false;
        $userId = 0;

        $db   = G::getMysqli();
        $stmt = $db->prepare("
            SELECT _geUserId
            FROM $this->tblUser
            WHERE email = ?
        ");
        $stmt->bind_param('s', $email);
        $stmt->bind_result($userId);
        $stmt->execute();
        $success = $stmt->fetch();
        $stmt->close();

        return ($success && $userId > 0) ? $userId : false;
    }


    function userEmailExists($email) {
        if (!$email) return false;
        $userId = 0;

        $db   = G::getMysqli();
        $stmt = $db->prepare("
            SELECT _geUserId
            FROM $this->tblUser
            WHERE email = ?
        ");
        $stmt->bind_param('s', $email);
        $stmt->bind_result($userId);
        $stmt->execute();
        $success = $stmt->fetch();
        $stmt->close();

        return $success;
    }


    // create account and email verification

    function registerRequest($name, $email, $password): string {
        // first, delete all previous verifications but leave the last 5
        $db   = G::getMysqli();
        $stmt = $db->prepare("
            DELETE FROM $this->tblUserRegisterRequest
            WHERE email = ? AND token NOT IN (
                SELECT token
                FROM (
                    SELECT token
                    FROM $this->tblUserRegisterRequest
                    WHERE email = ?
                    ORDER BY timestampCreated DESC
                    LIMIT 0, 4
                ) x
            )");
        $stmt->bind_param('ss', $email, $email);
        $stmt->execute();
        $stmt->close();

        $token = bin2hex(openssl_random_pseudo_bytes($this->cryptoNrOfBytes));

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare("
            INSERT INTO $this->tblUserRegisterRequest (
                token,
                name,
                email,
                passwordHash
            )
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param('ssss', $token, $name, $email, $passwordHash);
        $stmt->execute();
        $stmt->close();

        return $token;
    }


    function registerValid($email, $token) {
        $db   = G::getMysqli();
        $stmt = $db->prepare("
            SELECT 1
            FROM $this->tblUserRegisterRequest
            WHERE
                token = ? AND
                email = ? AND
                timestampCreated > NOW() - INTERVAL 1 DAY
        ");
        $stmt->bind_param('ss', $token, $email);
        $stmt->execute();
        $result = $stmt->fetch();
        $stmt->close();

        return $result;
    }


    function registerFinish($email, $lang) {
        $db   = G::getMysqli();
        $stmt = $db->prepare("
            INSERT INTO $this->tblUser (
                name,
                email,
                passwordHash
            )
            SELECT
                name,
                email,
                passwordHash
            FROM $this->tblUserRegisterRequest
            WHERE $this->tblUserRegisterRequest.email = ?
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $insertedId = $stmt->insert_id;
        $stmt->close();


        // update lang
        $stmt = $db->prepare("
            INSERT INTO $this->tblUserOption (_geUserId, name, value)
            VALUES (?, 'lang', ?)
        ");
        $stmt->bind_param('ds', $insertedId, $lang);
        $stmt->execute();
        $insertedId = $stmt->insert_id;
        $stmt->close();

        $stmt = $db->prepare("
            DELETE FROM $this->tblUserRegisterRequest
            WHERE email = ?
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
    }


    function registerDeleteRequests($email) {
        $db   = G::getMysqli();
        $stmt = $db->prepare("
            DELETE FROM $this->tblUserRegisterRequest
            WHERE email = ?
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
    }


    // recover account / password reset

    function passwordResetRequest($email): string {
        $db   = G::getMysqli();
        $stmt = $db->prepare("
            DELETE FROM $this->tblUserPasswordResetRequest
            WHERE email = ?
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();

        $token = bin2hex(openssl_random_pseudo_bytes($this->cryptoNrOfBytes));

        $stmt = $db->prepare("
            INSERT INTO $this->tblUserPasswordResetRequest (
                token,
                email
            )
            VALUES (?, ?)
        ");
        $stmt->bind_param('ss', $token, $email);
        $stmt->execute();
        $stmt->close();

        return $token;
    }


    function passwordResetValid($email, $token) {
        $db   = G::getMysqli();
        $stmt = $db->prepare("
            SELECT token
            FROM $this->tblUserPasswordResetRequest
            WHERE
                token = ? AND
                email = ? AND
                timestampCreated > NOW() - INTERVAL 1 HOUR
        ");
        $stmt->bind_param('ss', $token, $email);
        // $stmt->bind_result($token);
        $stmt->execute();
        $result = $stmt->fetch();
        $stmt->close();

        return $result;
    }


    // change password and verification

    function passwordResetFinish($email, $password) {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $db           = G::getMysqli();
        $stmt         = $db->prepare("
            UPDATE $this->tblUser
            SET passwordHash = ?
            WHERE email = ?
        ");
        $stmt->bind_param('ss', $passwordHash, $email);
        $stmt->execute();
        $stmt->close();

        $stmt = $db->prepare("
            DELETE FROM $this->tblUserPasswordResetRequest
            WHERE email = ?
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
    }


    function passwordResetDeleteRequests($email) {
        $db   = G::getMysqli();
        $stmt = $db->prepare("
            DELETE FROM $this->tblUserPasswordResetRequest
            WHERE email = ?
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
    }


    // change email and verification

    function emailChangeRequest($userId, $emailNew): string {

        // first, delete all previous verifications
        $db   = G::getMysqli();
        $stmt = $db->prepare("
            DELETE FROM $this->tblUserEmailChangeRequest
            WHERE _geUserId = ?
        ");
        $stmt->bind_param('d', $userId);
        $stmt->execute();
        $stmt->close();

        $token = bin2hex(openssl_random_pseudo_bytes($this->cryptoNrOfBytes));

        $stmt = $db->prepare("
            INSERT INTO $this->tblUserEmailChangeRequest (
                token,
                _geUserId,
                emailNew
            )
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('sds', $token, $userId, $emailNew);
        $stmt->execute();
        $stmt->close();

        return $token;
    }


    function emailChangeTo($userId) {
        if (!$userId) return false;
        $emailNew = '';

        $db   = G::getMysqli();
        $stmt = $db->prepare("
            SELECT emailNew
            FROM $this->tblUserEmailChangeRequest
            WHERE
                _geUserId = ? AND
                timestampCreated > NOW() - INTERVAL 1 DAY
        ");
        $stmt->bind_param('d', $userId);
        $stmt->bind_result($emailNew);
        $stmt->execute();
        $success = $stmt->fetch();
        $stmt->close();

        return ($success) ? $emailNew : false;
    }


    function emailChangeValid($userId, $token) {
        if (!$userId || !$token) return false;

        $db   = G::getMysqli();
        $stmt = $db->prepare("
            SELECT token
            FROM $this->tblUserEmailChangeRequest
            WHERE
                _geUserId = ? AND
                token = ? AND
                timestampCreated > NOW() - INTERVAL 1 DAY
            ORDER BY timestampCreated DESC
            LIMIT 0, 1
        ");
        $stmt->bind_param('ds', $userId, $token);
        $stmt->bind_result($token);
        $stmt->execute();
        $result = $stmt->fetch();
        $stmt->close();

        return $result;
    }


    function emailChangeFinish($userId, $oldEmail, $token) {
        if (!$userId || !$oldEmail || !$token) return;
        $emailNew = '';
        $db       = G::getMysqli();
        $stmt     = $db->prepare("
            SELECT emailNew
            FROM $this->tblUserEmailChangeRequest
            WHERE
                _geUserId = ? AND
                token = ? AND
                timestampCreated > NOW() - INTERVAL 1 DAY
            ORDER BY timestampCreated DESC
            LIMIT 0, 1
        ");
        $stmt->bind_param('ds', $userId, $token);
        $stmt->bind_result($emailNew);
        $stmt->execute();
        $stmt->fetch();
        $stmt->close();

        // set new email
        $stmt = $db->prepare("
            UPDATE $this->tblUser
            SET email = ?
            WHERE _geUserId = ?
        ");
        $stmt->bind_param('ss', $emailNew, $userId);
        $stmt->execute();
        $stmt->close();

        // delete the email change verification token
        $stmt = $db->prepare("
            DELETE FROM $this->tblUserEmailChangeRequest
            WHERE _geUserId = ?
        ");
        $stmt->bind_param('d', $userId);
        $stmt->execute();
        $stmt->close();
    }


    function emailChangeDeleteRequests($userId) {
        $db   = G::getMysqli();
        $stmt = $db->prepare("
            DELETE FROM $this->tblUserEmailChangeRequest
            WHERE _geUserId = ?
        ");
        $stmt->bind_param('d', $userId);
        $stmt->execute();
        $stmt->close();
    }


    function passwordChange($userId, $password): bool {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $db   = G::getMysqli();
        $stmt = $db->prepare("
            UPDATE $this->tblUser
            SET passwordHash = ?
            WHERE _geUserId = ?
        ");
        $stmt->bind_param('sd', $passwordHash, $userId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }


    function logout() {
        Flash::cleanMessages();
        session_destroy();
        foreach ($_COOKIE as $key => $val) {
            setcookie(
                $key,
                '0',
                [
                    'expires'  => 1,
                    'path'     => '/',
                    'domain'   => '.' . $_SERVER['SERVER_NAME'],
                    'secure'   => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]
            );
        }
    }

}
