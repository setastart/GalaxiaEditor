<?php


namespace GalaxiaEditor\model;


use Galaxia\G;
use Galaxia\Sql;


class ModelField {

    static function insert(
        array $gcUpdate,
        string $itemColId, string $itemId,
        string $fieldCol, string $fieldKey,
        array $insert
    ): ?int {

        // geD(
        //     $gcUpdate,
        //     $itemColId, $itemId,
        //     $fieldCol, $fieldKey,
        //     $insert
        // );

        $db = G::getMysqli();

        $insertedId = null;
        $values     = [];

        $insert = array_merge([$itemColId => $itemId, $fieldCol => $fieldKey], $insert);

        foreach ($insert as $inputName => $value) {
            $values[] = $value;
        }

        $query = Sql::queryInsert($gcUpdate, $insert);

        // geD($query, $values);
        $stmt  = $db->prepare($query);
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        if ($success) $insertedId = $stmt->insert_id;
        $stmt->close();

        return $insertedId;
    }




    static function update(
        array $gcUpdate,
        array $queryUpdateWhere,
        array $params,
        array $update
    ): int {


        $db = G::getMysqli();

        $affectedRows = null;

        $query = Sql::update($gcUpdate);
        $query .= Sql::updateSet(array_keys($update));
        $query .= Sql::updateWhere($queryUpdateWhere);

        // dd(
        //     $gcUpdate,
        //     $queryUpdateWhere,
        //     $params,
        //     $update,
        //     $query
        // );

        $stmt  = $db->prepare($query);
        $types = str_repeat('s', count($update)) . 'dds';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        return $affectedRows;
    }




}
