<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace GalaxiaEditor\model;


use Galaxia\G;
use Galaxia\Text;


class ModelList {

    static function order(string $table, array $order): int {
        $affected = 0;
        $stmt = null;
        foreach ($order as $old => $new) {
            $q = 'UPDATE ' . Text::q($table) .
                  ' SET position = ' . (int)$new .
                  ' WHERE ' . Text::q($table . 'Id') . ' = ' . (int)$old . ';' . PHP_EOL;
            $stmt = G::prepare($q);
            $stmt->execute();
            $affected += $stmt->affected_rows;
        }
        if (!is_null($stmt)) $stmt->close();

        return $affected;
    }

}
