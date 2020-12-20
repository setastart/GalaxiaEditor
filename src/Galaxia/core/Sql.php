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


use mysqli;


class Sql {

    public const ALLOWED_MODS        = ['COUNT', 'MIN', 'MAX', 'ANY_VALUE', 'DATE', 'TIME', 'YEAR', 'MONTH', 'DAY'];
    public const ALLOWED_WHERE_LOGIC = ['=', '<', '>', '<=', '>=', '<=>', 'BETWEEN', 'IS NOT NULL', 'IS NULL', 'NOT IN'];


    static function queryInsert($expression, $changes, array $langs = null) {
        $firstTable = key($expression);
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'INSERT INTO ' . Text::q($firstTable) . ' (' . PHP_EOL;
        foreach ($changes as $key => $value) {
            $r .= '    ' . Text::q($key) . ',' . PHP_EOL;
        }
        $r = rtrim($r, ',' . PHP_EOL) . PHP_EOL . ')' . PHP_EOL . PHP_EOL;

        $r .= 'VALUES' . PHP_EOL;
        $r .= '    (';
        foreach ($changes as $key => $value) {
            $r .= '?, ';
        }
        $r = rtrim($r, ', ') . ')' . PHP_EOL . PHP_EOL;

        return $r;
    }



    static function select(array $expression, array $langs = null) {
        $firstTable = key($expression);
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = PHP_EOL . 'SELECT ' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $mods) {

                if (is_array($mods)) {
                    foreach ($mods as $mod) {
                        if (!in_array($mod, Sql::ALLOWED_MODS)) continue;
                        $r .= '    ' . $mod . '(' . Text::q($table) . '.' . Text::q($column) . ') AS ' . Text::q($column . $mod) . ', ' . PHP_EOL;
                    }
                    continue;
                }

                $mod = $mods;
                if (!is_int($column) && in_array($mod, Sql::ALLOWED_MODS)) {
                    $r .= '    ' . $mod . '(' . Text::q($table) . '.' . Text::q($column) . ') AS ' . Text::q($column . $mod) . ', ' . PHP_EOL;
                    continue;
                } else if (substr($mod, 0, 3) == 'AS ') {
                    $r .= '    ' . Text::q($table) . '.' . Text::q($column) . ' AS ' . Text::q(substr($mod, 3)) . ', ' . PHP_EOL;
                    continue;
                }

                $column = $mod;
                if (substr($column, 0, 9) == 'timestamp') {
                    $r .= '    UNIX_TIMESTAMP(' . Text::q($table) . '.' . Text::q($column) . ') AS ' . Text::q($column) . ', ' . PHP_EOL;
                    continue;
                }

                $r .= '    ' . Text::q($table) . '.' . Text::q($column) . ', ' . PHP_EOL;
            }
        }
        $r = rtrim($r, ', ' . PHP_EOL) . PHP_EOL . PHP_EOL;

        $r .= 'FROM ' . Text::q($firstTable) . PHP_EOL . PHP_EOL;

        return $r;
    }




    static function selectOne(array $expression, array $langs = null) {
        $firstTable = key($expression);
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'SELECT 1' . PHP_EOL . PHP_EOL;
        $r .= 'FROM ' . Text::q($firstTable) . PHP_EOL . PHP_EOL;

        return $r;
    }




    static function selectFirst(array $expression, array $langs = null) {
        $firstTable = key($expression);
        if ($langs) ArrayShape::languify($expression, $langs);
        $firstColumn = $expression[$firstTable][0];

        $r = 'SELECT ' . Text::q($firstColumn) . PHP_EOL . PHP_EOL;
        $r .= 'FROM ' . Text::q($firstTable) . PHP_EOL . PHP_EOL;

        return $r;
    }




    static function selectCount(string $param) {
        return 'SELECT COUNT(' . Text::q($param) . ')' . PHP_EOL . PHP_EOL;
    }




    static function selectLeftJoinUsing(array $expression, array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = '';
        foreach ($expression as $table => $columns) {
            $r .= 'LEFT JOIN ' . Text::q($table) . ' USING (';
            foreach ($columns as $column) {
                $r .= Text::q($column) . ', ';
            }
            $r = rtrim($r, ', ') . ')' . PHP_EOL;
        }

        return $r . PHP_EOL . PHP_EOL;
    }




    static function leftJoinOnAnd(string $tblSrc, string $tblDes, string $colOn, string $colAnd): string {
        $r = 'LEFT JOIN ' . Text::q($tblSrc) .
             ' ON ' . Text::q($tblDes) . '.' . Text::q($colOn) . ' = ' . Text::q($tblSrc) . '.' . Text::q($colOn) . PHP_EOL;
        $r .= '    AND ' . Text::q($tblSrc) . '.' . Text::q($colAnd) . ' = ?' . PHP_EOL;

        return $r;
    }




    static function selectWhere(array $expression, array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'WHERE ' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $logic) {
                if (!in_array($logic, Sql::ALLOWED_WHERE_LOGIC)) $logic = '=';
                switch ($logic) {
                    case 'BETWEEN':
                        $r .= Text::q($table) . '.' . Text::q($column) . ' BETWEEN ? AND ?' . PHP_EOL;
                        break;
                    case 'IS NOT NULL':
                        $r .= Text::q($table) . '.' . Text::q($column) . ' IS NOT NULL AND' . PHP_EOL;
                        break;
                    default:
                        $r .= Text::q($table) . '.' . Text::q($column) . ' ' . $logic . ' ? AND' . PHP_EOL;
                        break;
                }
            }
        }

        return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    static function selectWherePrefix(array $expression, string $prefix = 'WHERE', string $operation = 'AND', array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = $prefix . ' (' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $logics) {
                if (is_string($logics)) $logics = [$logics];
                foreach ($logics as $logic) {
                    if (!in_array($logics, Sql::ALLOWED_WHERE_LOGIC)) $logics = '=';
                    switch ($logic) {
                        case 'BETWEEN':
                            $r .= Text::q($table) . '.' . Text::q($column) . ' BETWEEN ? AND ? ' . $operation . PHP_EOL;
                            break;
                        case 'IS NULL':
                            $r .= Text::q($table) . '.' . Text::q($column) . ' IS NULL ' . $operation . PHP_EOL;
                            break;
                        case 'IS NOT NULL':
                            $r .= Text::q($table) . '.' . Text::q($column) . ' IS NOT NULL ' . $operation . PHP_EOL;
                            break;
                        case 'NOT IN':
                            $r .= Text::q($column) . ' NOT IN (SELECT ' . Text::q($column) . ' FROM ' . Text::q($table) . ') ' . $operation . PHP_EOL;
                            break;
                        default:
                            $r .= Text::q($table) . '.' . Text::q($column) . ' ' . $logic . ' ? ' . $operation . PHP_EOL;
                            break;
                    }
                }
            }
        }

        return rtrim($r, ' ' . $operation . PHP_EOL) . PHP_EOL . ')' . PHP_EOL . PHP_EOL;
    }

    static function selectWhereRaw(array $expression, string $prefix = 'WHERE', string $operation = 'AND', array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = $prefix . ' (' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $raw) {
                $r .= Text::q($table) . '.' . Text::q($column) . ' ' . $raw . ' ' . $operation . PHP_EOL;
            }
        }

        return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . ')' . PHP_EOL . PHP_EOL;
    }




    static function selectWhereOr(array $expression, string $prefix = 'WHERE', array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = $prefix . ' (' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $logic) {
                if (!in_array($logic, Sql::ALLOWED_WHERE_LOGIC)) $logic = '=';
                switch ($logic) {
                    case 'BETWEEN':
                        $r .= Text::q($table) . '.' . Text::q($column) . ' BETWEEN ? AND ?' . PHP_EOL;
                        break;
                    case 'IS NOT NULL':
                        $r .= Text::q($table) . '.' . Text::q($column) . ' IS NOT NULL OR' . PHP_EOL;
                        break;
                    default:
                        $r .= Text::q($table) . '.' . Text::q($column) . ' ' . $logic . ' ? OR' . PHP_EOL;
                        break;
                }
            }
        }

        return rtrim($r, ' OR' . PHP_EOL) . PHP_EOL . ')' . PHP_EOL . PHP_EOL;
    }




    static function selectWhereIn(array $expression, array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'WHERE ' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $count) {
                $r .= Text::q($table) . '.' . Text::q($column) . ' IN (' . rtrim(str_repeat('?, ', $count), ', ') . ') AND' . PHP_EOL;
            }
        }

        // dd($r);
        return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    static function selectWhereAndIn(array $expression, array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'AND ' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $count) {
                $r .= Text::q($table) . '.' . Text::q($column) . ' IN (' . rtrim(str_repeat('?, ', $count), ', ') . ') AND' . PHP_EOL;
            }
        }

        // dd($r);
        return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    static function selectGroupBy(array $expression, array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'GROUP BY' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $mods) {

                if (is_array($mods)) {
                    foreach ($mods as $mod) {
                        if (!in_array($mod, Sql::ALLOWED_MODS)) continue;
                        $r .= $mod . '(' . Text::q($table) . '.' . Text::q($column) . '), ' . PHP_EOL;
                    }
                    continue;
                }

                $mod = $mods;
                if (!is_int($column) && in_array($mod, Sql::ALLOWED_MODS)) {
                    $r .= '    ' . $mod . '(' . Text::q($table) . '.' . Text::q($column) . '), ' . PHP_EOL;
                    continue;
                }

                $column = $mod;
                $r      .= Text::q($table) . '.' . Text::q($column) . ', ' . PHP_EOL;
            }
        }

        return rtrim($r, ', ' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    static function selectOrderBy(array $expression, array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'ORDER BY' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $orders) {

                if (is_array($orders)) {
                    foreach ($orders as $order => $mod) {
                        if (!in_array($order, ['ASC', 'DESC'])) $order = 'ASC';
                        if (!in_array($mod, Sql::ALLOWED_MODS)) continue;
                        $r .= $mod . '(' . Text::q($table) . '.' . Text::q($column) . ') ' . $order . ', ' . PHP_EOL;
                    }
                    continue;
                }

                $order = $orders;

                if (!in_array($order, ['ASC', 'DESC'])) $order = 'ASC';
                $r .= Text::q($table) . '.' . Text::q($column) . ' ' . $order . ', ' . PHP_EOL;
            }
        }

        return rtrim($r, ', ' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    static function selectLimit($offset, $count) {
        $offset = (string)$offset;
        $count  = (string)$count;
        $offset = ctype_digit($offset) ? (int)$offset : 0;
        $count  = ctype_digit($count) ? (int)$count : 1;

        return 'LIMIT ' . $offset . ', ' . $count . PHP_EOL . PHP_EOL;
    }




    static function update(array $expression, array $langs = null) {
        $firstTable = key($expression);
        if ($langs) ArrayShape::languify($expression, $langs);

        return 'UPDATE ' . Text::q($firstTable) . PHP_EOL . PHP_EOL;
    }




    static function updateSet(array $params) {
        $r = 'SET ' . PHP_EOL;

        foreach ($params as $param) {
            $r .= '    ' . Text::q($param) . ' = ?, ' . PHP_EOL;
        }

        return rtrim($r, ', ' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    static function updateWhere(array $expression, array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'WHERE ' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column) {
                $r .= '    ' . Text::q($table) . '.' . Text::q($column) . ' = ? AND' . PHP_EOL;
            }
        }

        return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    static function delete($expression, array $langs = null) {
        $firstTable = key($expression);
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'DELETE FROM ' . Text::q($firstTable) . PHP_EOL . PHP_EOL;

        $columns = $expression[$firstTable];
        $r       .= 'WHERE ' . PHP_EOL;
        foreach ($columns as $column) {
            $r .= '    ' . Text::q($column) . ' = ? AND' . PHP_EOL;
        }

        return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    static function deleteIn($table, $whereCols, $inCol, $ids) {
        $r = 'DELETE FROM ' . Text::q($table) . PHP_EOL . PHP_EOL;

        $r .= 'WHERE ' . PHP_EOL;
        foreach ($whereCols as $col)
            $r .= '    ' . Text::q($col) . ' = ? AND' . PHP_EOL;

        $r .= '    ' . Text::q($inCol) . ' IN' . PHP_EOL;
        $r .= '    (';
        foreach ($ids as $id)
            $r .= '?, ';

        return rtrim($r, ', ') . ')' . PHP_EOL;
    }




    static function deleteOrNull($expression, array $langs = null) {
        $firstTable = key($expression);
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'DELETE FROM ' . Text::q($firstTable) . PHP_EOL . PHP_EOL;

        $columns = $expression[$firstTable];
        $r       .= 'WHERE ' . PHP_EOL;
        foreach ($columns as $column) {
            $r .= '    (' . Text::q($column) . ' = ? OR ' . Text::q($column) . ' IS NULL) AND' . PHP_EOL;
        }

        return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    static function chunkSelect(mysqli $db, string $sql, callable $f, array &$items = [], $chunkSize = 5000) {
        $done       = 0;
        $askForData = true;
        do {
            $chunk = $sql . PHP_EOL . 'LIMIT ' . $done . ', ' . $chunkSize . PHP_EOL;

            $stmt = $db->prepare($chunk);
            $stmt->execute();
            $result   = $stmt->get_result();
            $rowCount = $stmt->affected_rows;

            if ($rowCount) {
                $done += $rowCount;
                $f($result, $items);
            } else {
                $askForData = false;
            }


            $stmt->close();

        } while ($askForData);

        return $items;
    }

}
