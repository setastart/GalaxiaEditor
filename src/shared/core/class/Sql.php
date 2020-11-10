<?php


namespace Galaxia;


use mysqli;


class Sql {

    public const ALLOWED_MODS        = ['COUNT', 'MIN', 'MAX', 'ANY_VALUE', 'DATE', 'TIME', 'YEAR', 'MONTH', 'DAY'];
    public const ALLOWED_WHERE_LOGIC = ['=', '<', '>', '<=', '>=', 'BETWEEN', 'IS NOT NULL', 'IS NULL', 'NOT IN'];


    public static function queryInsert($expression, $changes, array $langs = null) {
        $firstTable = key($expression);
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'INSERT INTO ' . q($firstTable) . ' (' . PHP_EOL;
        foreach ($changes as $key => $value) {
            $r .= '    ' . q($key) . ',' . PHP_EOL;
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




    public static function select(array $expression, array $langs = null) {
        $firstTable = key($expression);
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = PHP_EOL . 'SELECT ' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $mods) {

                if (is_array($mods)) {
                    foreach ($mods as $mod) {
                        if (!in_array($mod, Sql::ALLOWED_MODS)) continue;
                        $r .= '    ' . $mod . '(' . q($table) . '.' . q($column) . ') AS ' . q($column . $mod) . ', ' . PHP_EOL;
                    }
                    continue;
                }

                $mod = $mods;
                if (!is_int($column) && in_array($mod, Sql::ALLOWED_MODS)) {
                    $r .= '    ' . $mod . '(' . q($table) . '.' . q($column) . ') AS ' . q($column . $mod) . ', ' . PHP_EOL;
                    continue;
                } else if (substr($mod, 0, 3) == 'AS ') {
                    $r .= '    ' . q($table) . '.' . q($column) . ' AS ' . q(substr($mod, 3)) . ', ' . PHP_EOL;
                    continue;
                }

                $column = $mod;
                if (substr($column, 0, 9) == 'timestamp') {
                    $r .= '    UNIX_TIMESTAMP(' . q($table) . '.' . q($column) . ') AS ' . q($column) . ', ' . PHP_EOL;
                    continue;
                }

                $r .= '    ' . q($table) . '.' . q($column) . ', ' . PHP_EOL;
            }
        }
        $r = rtrim($r, ', ' . PHP_EOL) . PHP_EOL . PHP_EOL;

        $r .= 'FROM ' . q($firstTable) . PHP_EOL . PHP_EOL;

        return $r;
    }




    public static function selectOne(array $expression, array $langs = null) {
        $firstTable = key($expression);
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'SELECT 1' . PHP_EOL . PHP_EOL;
        $r .= 'FROM ' . q($firstTable) . PHP_EOL . PHP_EOL;

        return $r;
    }




    public static function selectFirst(array $expression, array $langs = null) {
        $firstTable = key($expression);
        if ($langs) ArrayShape::languify($expression, $langs);
        $firstColumn = $expression[$firstTable][0];

        $r = 'SELECT ' . q($firstColumn) . PHP_EOL . PHP_EOL;
        $r .= 'FROM ' . q($firstTable) . PHP_EOL . PHP_EOL;

        return $r;
    }




    public static function selectCount(string $param) {
        return 'SELECT COUNT(' . q($param) . ')' . PHP_EOL . PHP_EOL;
    }




    public static function selectLeftJoinUsing(array $expression, array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = '';
        foreach ($expression as $table => $columns) {
            $r .= 'LEFT JOIN ' . q($table) . ' USING (';
            foreach ($columns as $column) {
                $r .= q($column) . ', ';
            }
            $r = rtrim($r, ', ') . ')' . PHP_EOL;
        }

        return $r . PHP_EOL . PHP_EOL;
    }




    public static function selectWhere(array $expression, array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'WHERE ' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $logic) {
                if (!in_array($logic, Sql::ALLOWED_WHERE_LOGIC)) $logic = '=';
                switch ($logic) {
                    case 'BETWEEN':
                        $r .= q($table) . '.' . q($column) . ' BETWEEN ? AND ?' . PHP_EOL;
                        break;
                    case 'IS NOT NULL':
                        $r .= q($table) . '.' . q($column) . ' IS NOT NULL AND' . PHP_EOL;
                        break;
                    default:
                        $r .= q($table) . '.' . q($column) . ' ' . $logic . ' ? AND' . PHP_EOL;
                        break;
                }
            }
        }

        return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    public static function selectWherePrefix(array $expression, string $prefix = 'WHERE', string $operation = 'AND', array $langs = null) {
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
                            $r .= q($table) . '.' . q($column) . ' BETWEEN ? AND ? ' . $operation . PHP_EOL;
                            break;
                        case 'IS NULL':
                            $r .= q($table) . '.' . q($column) . ' IS NULL ' . $operation . PHP_EOL;
                            break;
                        case 'IS NOT NULL':
                            $r .= q($table) . '.' . q($column) . ' IS NOT NULL ' . $operation . PHP_EOL;
                            break;
                        case 'NOT IN':
                            $r .= q($column) . ' NOT IN (SELECT ' . q($column) . ' FROM ' . q($table) . ') ' . $operation . PHP_EOL;
                            break;
                        default:
                            $r .= q($table) . '.' . q($column) . ' ' . $logic . ' ? ' . $operation . PHP_EOL;
                            break;
                    }
                }
            }
        }

        return rtrim($r, ' ' . $operation . PHP_EOL) . PHP_EOL . ')' . PHP_EOL . PHP_EOL;
    }

    public static function selectWhereRaw(array $expression, string $prefix = 'WHERE', string $operation = 'AND', array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = $prefix . ' (' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $raw) {
                $r .= q($table) . '.' . q($column) . ' ' . $raw . ' ' . $operation . PHP_EOL;
            }
        }

        return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . ')' . PHP_EOL . PHP_EOL;
    }




    public static function selectWhereOr(array $expression, string $prefix = 'WHERE', array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = $prefix . ' (' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $logic) {
                if (!in_array($logic, Sql::ALLOWED_WHERE_LOGIC)) $logic = '=';
                switch ($logic) {
                    case 'BETWEEN':
                        $r .= q($table) . '.' . q($column) . ' BETWEEN ? AND ?' . PHP_EOL;
                        break;
                    case 'IS NOT NULL':
                        $r .= q($table) . '.' . q($column) . ' IS NOT NULL OR' . PHP_EOL;
                        break;
                    default:
                        $r .= q($table) . '.' . q($column) . ' ' . $logic . ' ? OR' . PHP_EOL;
                        break;
                }
            }
        }

        return rtrim($r, ' OR' . PHP_EOL) . PHP_EOL . ')' . PHP_EOL . PHP_EOL;
    }




    public static function selectWhereIn(array $expression, array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'WHERE ' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $count) {
                $r .= q($table) . '.' . q($column) . ' IN (' . rtrim(str_repeat('?, ', $count), ', ') . ') AND' . PHP_EOL;
            }
        }

        // dd($r);
        return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    public static function selectWhereAndIn(array $expression, array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'AND ' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $count) {
                $r .= q($table) . '.' . q($column) . ' IN (' . rtrim(str_repeat('?, ', $count), ', ') . ') AND' . PHP_EOL;
            }
        }

        // dd($r);
        return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    public static function selectGroupBy(array $expression, array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'GROUP BY' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $mods) {

                if (is_array($mods)) {
                    foreach ($mods as $mod) {
                        if (!in_array($mod, Sql::ALLOWED_MODS)) continue;
                        $r .= $mod . '(' . q($table) . '.' . q($column) . '), ' . PHP_EOL;
                    }
                    continue;
                }

                $mod = $mods;
                if (!is_int($column) && in_array($mod, Sql::ALLOWED_MODS)) {
                    $r .= '    ' . $mod . '(' . q($table) . '.' . q($column) . '), ' . PHP_EOL;
                    continue;
                }

                $column = $mod;
                $r      .= q($table) . '.' . q($column) . ', ' . PHP_EOL;
            }
        }

        return rtrim($r, ', ' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    public static function selectOrderBy(array $expression, array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'ORDER BY' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column => $orders) {

                if (is_array($orders)) {
                    foreach ($orders as $order => $mod) {
                        if (!in_array($order, ['ASC', 'DESC'])) $order = 'ASC';
                        if (!in_array($mod, Sql::ALLOWED_MODS)) continue;
                        $r .= $mod . '(' . q($table) . '.' . q($column) . ') ' . $order . ', ' . PHP_EOL;
                    }
                    continue;
                }

                $order = $orders;

                if (!in_array($order, ['ASC', 'DESC'])) $order = 'ASC';
                $r .= q($table) . '.' . q($column) . ' ' . $order . ', ' . PHP_EOL;
            }
        }

        return rtrim($r, ', ' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    public static function selectLimit($offset, $count) {
        $offset = (string)$offset;
        $count  = (string)$count;
        $offset = ctype_digit($offset) ? (int)$offset : 0;
        $count  = ctype_digit($count) ? (int)$count : 1;

        return 'LIMIT ' . $offset . ', ' . $count . PHP_EOL . PHP_EOL;
    }




    public static function update(array $expression, array $langs = null) {
        $firstTable = key($expression);
        if ($langs) ArrayShape::languify($expression, $langs);

        return 'UPDATE ' . q($firstTable) . PHP_EOL . PHP_EOL;
    }




    public static function updateSet(array $params) {
        $r = 'SET ' . PHP_EOL;

        foreach ($params as $param) {
            $r .= '    ' . q($param) . ' = ?, ' . PHP_EOL;
        }

        return rtrim($r, ', ' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    public static function updateWhere(array $expression, array $langs = null) {
        if (empty($expression)) return '';
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'WHERE ' . PHP_EOL;
        foreach ($expression as $table => $columns) {
            foreach ($columns as $column) {
                $r .= '    ' . q($table) . '.' . q($column) . ' = ? AND' . PHP_EOL;
            }
        }

        return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    public static function delete($expression, array $langs = null) {
        $firstTable = key($expression);
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'DELETE FROM ' . q($firstTable) . PHP_EOL . PHP_EOL;

        $columns = $expression[$firstTable];
        $r       .= 'WHERE ' . PHP_EOL;
        foreach ($columns as $column) {
            $r .= '    ' . q($column) . ' = ? AND' . PHP_EOL;
        }

        return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    public static function deleteIn($table, $whereCols, $inCol, $ids) {
        $r = 'DELETE FROM ' . q($table) . PHP_EOL . PHP_EOL;

        $r .= 'WHERE ' . PHP_EOL;
        foreach ($whereCols as $col)
            $r .= '    ' . q($col) . ' = ? AND' . PHP_EOL;

        $r .= '    ' . q($inCol) . ' IN' . PHP_EOL;
        $r .= '    (';
        foreach ($ids as $id)
            $r .= '?, ';

        return rtrim($r, ', ') . ')' . PHP_EOL;
    }




    public static function deleteOrNull($expression, array $langs = null) {
        // geD($expression);
        $firstTable = key($expression);
        if ($langs) ArrayShape::languify($expression, $langs);

        $r = 'DELETE FROM ' . q($firstTable) . PHP_EOL . PHP_EOL;

        $columns = $expression[$firstTable];
        $r       .= 'WHERE ' . PHP_EOL;
        foreach ($columns as $column) {
            $r .= '    (' . q($column) . ' = ? OR ' . q($column) . ' IS NULL) AND' . PHP_EOL;
        }

        return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
    }




    public static function chunkSelect(mysqli $db, string $sql, callable $f, array &$items = [], $chunkSize = 5000) {
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
