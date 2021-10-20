<?php


namespace GalaxiaEditor\history;


use Exception;
use Galaxia\G;
use Galaxia\Sql;


class History {

    public static function insert($uniqueId, $tabName, $tabId, $inputKey, $fieldKey, $action, $content, $userId) {
        // $action == 0: delete
        // $action == 1: save
        // $action == 2: update
        // $action == 3: create

        if ($action == 3 || $action == 0)
            if ($content == '') return null;

        if ($inputKey == 'passwordCurrent') return null;
        if ($inputKey == 'passwordRepeat') return null;
        if (substr($inputKey, 0, 8) == 'password') $content = '****************';

        $changes = [
            '_geUserId' => $userId,
            'uniqueId'  => $uniqueId,
            'action'    => $action,
            'tabName'   => $tabName,
            'tabId'     => $tabId,
            'fieldKey'  => $fieldKey,
            'inputKey'  => $inputKey,
            'content'   => $content,
        ];
        $values  = array_values($changes);
        $query   = Sql::queryInsert(['_geHistory' => ['_geUserId', 'uniqueId', 'action', 'tabName', 'tabId', 'fieldKey', 'inputKey', 'content']], $changes);
        try {
            $db    = G::getMysqli();
            $stmt  = $db->prepare($query);
            $types = str_repeat('s', count($values));
            $stmt->bind_param($types, ...$values);
            $success = $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            echo 'Unable to insert history: ' . $tabName . PHP_EOL;
            echo $e->getMessage() . PHP_EOL;

            return false;
        }

        return true;
    }

}
