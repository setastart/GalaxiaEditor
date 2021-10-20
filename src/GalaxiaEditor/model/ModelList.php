<?php
/*
 Copyright 2017-2020 Ino Detelić & Zaloa G. Ramos

  - Licensed under the EUPL, Version 1.2 only (the "Licence");
  - You may not use this work except in compliance with the Licence.

  - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

  - Unless required by applicable law or agreed to in writing, software distributed
    under the Licence is distributed on an "AS IS" basis,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  - See the Licence for the specific language governing permissions and limitations under the Licence.
 */

namespace GalaxiaEditor\model;


use Galaxia\G;
use Galaxia\Text;


class ModelList {

    static function order(string $table, array $order): int {
        $db = G::getMysqli();

        $affected = 0;
        foreach ($order as $old => $new) {
            $q = 'UPDATE ' . Text::q($table) .
                  ' SET position = ' . (int)$new .
                  ' WHERE ' . Text::q($table . 'Id') . ' = ' . (int)$old . ';' . PHP_EOL;
            $stmt = $db->prepare($q);
            $stmt->execute();
            $affected += $stmt->affected_rows;
        }
        $stmt->close();

        return $affected;
    }

}
