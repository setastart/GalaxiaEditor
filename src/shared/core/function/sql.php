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

const ALLOWED_MODS        = ['COUNT', 'MIN', 'MAX', 'ANY_VALUE', 'DATE', 'TIME', 'YEAR', 'MONTH', 'DAY'];
const ALLOWED_WHERE_LOGIC = ['=', '<', '>', '<=', '>=', 'BETWEEN', 'IS NOT NULL', 'IS NULL', 'NOT IN'];




// INSERT

function queryInsert($expression, $changes, array $langs = null) {
    $firstTable = key($expression);
    if ($langs) arrayLanguify($expression, $langs);

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




// SELECT

function querySelect(array $expression, array $langs = null) {
    $firstTable = key($expression);
    if ($langs) arrayLanguify($expression, $langs);

    $r = PHP_EOL . 'SELECT ' . PHP_EOL;
    foreach ($expression as $table => $columns) {
        foreach ($columns as $column => $mods) {

            if (is_array($mods)) {
                foreach ($mods as $mod) {
                    if (!in_array($mod, ALLOWED_MODS)) continue;
                    $r .= '    ' . $mod . '(' . q($table) . '.' . q($column) . ') AS ' . q($column . $mod) . ', ' . PHP_EOL;
                }
                continue;
            }

            $mod = $mods;
            if (!is_int($column) && in_array($mod, ALLOWED_MODS)) {
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


function querySelectOne(array $expression, array $langs = null) {
    $firstTable = key($expression);
    if ($langs) arrayLanguify($expression, $langs);

    $r = 'SELECT 1' . PHP_EOL . PHP_EOL;
    $r .= 'FROM ' . q($firstTable) . PHP_EOL . PHP_EOL;

    return $r;
}


function querySelectFirst(array $expression, array $langs = null) {
    $firstTable = key($expression);
    if ($langs) arrayLanguify($expression, $langs);
    $firstColumn = $expression[$firstTable][0];

    $r = 'SELECT ' . q($firstColumn) . PHP_EOL . PHP_EOL;
    $r .= 'FROM ' . q($firstTable) . PHP_EOL . PHP_EOL;

    return $r;
}


function querySelectCount(string $param) {
    return 'SELECT COUNT(' . q($param) . ')' . PHP_EOL . PHP_EOL;
}


function querySelectLeftJoinUsing(array $expression, array $langs = null) {
    if (empty($expression)) return '';
    if ($langs) arrayLanguify($expression, $langs);

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


function querySelectWhere(array $expression, array $langs = null) {
    if (empty($expression)) return '';
    if ($langs) arrayLanguify($expression, $langs);

    $r = 'WHERE ' . PHP_EOL;
    foreach ($expression as $table => $columns) {
        foreach ($columns as $column => $logic) {
            if (!in_array($logic, ALLOWED_WHERE_LOGIC)) $logic = '=';
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


function querySelectWherePrefix(array $expression, string $prefix = 'WHERE', string $operation = 'AND', array $langs = null) {
    if (empty($expression)) return '';
    if ($langs) arrayLanguify($expression, $langs);

    $r = $prefix . ' (' . PHP_EOL;
    foreach ($expression as $table => $columns) {
        foreach ($columns as $column => $logics) {
            if (is_string($logics)) $logics = [$logics];
            foreach ($logics as $logic) {
                if (!in_array($logics, ALLOWED_WHERE_LOGIC)) $logics = '=';
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


function querySelectWhereRaw(array $expression, string $prefix = 'WHERE', string $operation = 'AND', array $langs = null) {
    if (empty($expression)) return '';
    if ($langs) arrayLanguify($expression, $langs);

    $r = $prefix . ' (' . PHP_EOL;
    foreach ($expression as $table => $columns) {
        foreach ($columns as $column => $raw) {
            $r .= q($table) . '.' . q($column) . ' ' . $raw . ' ' . $operation . PHP_EOL;
        }
    }

    return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . ')' . PHP_EOL . PHP_EOL;
}


function querySelectWhereOr(array $expression, string $prefix = 'WHERE', array $langs = null) {
    if (empty($expression)) return '';
    if ($langs) arrayLanguify($expression, $langs);

    $r = $prefix . ' (' . PHP_EOL;
    foreach ($expression as $table => $columns) {
        foreach ($columns as $column => $logic) {
            if (!in_array($logic, ALLOWED_WHERE_LOGIC)) $logic = '=';
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


function querySelectWhereIn(array $expression, array $langs = null) {
    if (empty($expression)) return '';
    if ($langs) arrayLanguify($expression, $langs);

    $r = 'WHERE ' . PHP_EOL;
    foreach ($expression as $table => $columns) {
        foreach ($columns as $column => $count) {
            $r .= q($table) . '.' . q($column) . ' IN (' . rtrim(str_repeat('?, ', $count), ', ') . ') AND' . PHP_EOL;
        }
    }

    // ddp($r);
    return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
}


function querySelectWhereAndIn(array $expression, array $langs = null) {
    if (empty($expression)) return '';
    if ($langs) arrayLanguify($expression, $langs);

    $r = 'AND ' . PHP_EOL;
    foreach ($expression as $table => $columns) {
        foreach ($columns as $column => $count) {
            $r .= q($table) . '.' . q($column) . ' IN (' . rtrim(str_repeat('?, ', $count), ', ') . ') AND' . PHP_EOL;
        }
    }

    // ddp($r);
    return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
}


function querySelectGroupBy(array $expression, array $langs = null) {
    if (empty($expression)) return '';
    if ($langs) arrayLanguify($expression, $langs);

    $r = 'GROUP BY' . PHP_EOL;
    foreach ($expression as $table => $columns) {
        foreach ($columns as $column => $mods) {

            if (is_array($mods)) {
                foreach ($mods as $mod) {
                    if (!in_array($mod, ALLOWED_MODS)) continue;
                    $r .= $mod . '(' . q($table) . '.' . q($column) . '), ' . PHP_EOL;
                }
                continue;
            }

            $mod = $mods;
            if (!is_int($column) && in_array($mod, ALLOWED_MODS)) {
                $r .= '    ' . $mod . '(' . q($table) . '.' . q($column) . '), ' . PHP_EOL;
                continue;
            }

            $column = $mod;
            $r      .= q($table) . '.' . q($column) . ', ' . PHP_EOL;
        }
    }

    return rtrim($r, ', ' . PHP_EOL) . PHP_EOL . PHP_EOL;
}


function querySelectOrderBy(array $expression, array $langs = null) {
    if (empty($expression)) return '';
    if ($langs) arrayLanguify($expression, $langs);

    $r = 'ORDER BY' . PHP_EOL;
    foreach ($expression as $table => $columns) {
        foreach ($columns as $column => $orders) {

            if (is_array($orders)) {
                foreach ($orders as $order => $mod) {
                    if (!in_array($order, ['ASC', 'DESC'])) $order = 'ASC';
                    if (!in_array($mod, ALLOWED_MODS)) continue;
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


function querySelectLimit($offset, $count) {
    $offset = (string)$offset;
    $count  = (string)$count;
    $offset = ctype_digit($offset) ? (int)$offset : 0;
    $count  = ctype_digit($count) ? (int)$count : 1;

    return 'LIMIT ' . $offset . ', ' . $count . PHP_EOL . PHP_EOL;
}




// UPDATE

function queryUpdate(array $expression, array $langs = null) {
    $firstTable = key($expression);
    if ($langs) arrayLanguify($expression, $langs);

    return 'UPDATE ' . q($firstTable) . PHP_EOL . PHP_EOL;
}

function queryUpdateSet(array $params) {
    $r = 'SET ' . PHP_EOL;

    foreach ($params as $param) {
        $r .= '    ' . q($param) . ' = ?, ' . PHP_EOL;
    }

    return rtrim($r, ', ' . PHP_EOL) . PHP_EOL . PHP_EOL;
}

function queryUpdateWhere(array $expression, array $langs = null) {
    if (empty($expression)) return '';
    if ($langs) arrayLanguify($expression, $langs);

    $r = 'WHERE ' . PHP_EOL;
    foreach ($expression as $table => $columns) {
        foreach ($columns as $column) {
            $r .= '    ' . q($table) . '.' . q($column) . ' = ? AND' . PHP_EOL;
        }
    }

    return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
}




// DELETE

function queryDelete($expression, array $langs = null) {
    $firstTable = key($expression);
    if ($langs) arrayLanguify($expression, $langs);

    $r = 'DELETE FROM ' . q($firstTable) . PHP_EOL . PHP_EOL;

    $columns = $expression[$firstTable];
    $r       .= 'WHERE ' . PHP_EOL;
    foreach ($columns as $column) {
        $r .= '    ' . q($column) . ' = ? AND' . PHP_EOL;
    }

    return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
}


function queryDeleteIn($table, $whereCols, $inCol, $ids) {
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


function queryDeleteOrNull($expression, array $langs = null) {
    // geD($expression);
    $firstTable = key($expression);
    if ($langs) arrayLanguify($expression, $langs);

    $r = 'DELETE FROM ' . q($firstTable) . PHP_EOL . PHP_EOL;

    $columns = $expression[$firstTable];
    $r       .= 'WHERE ' . PHP_EOL;
    foreach ($columns as $column) {
        $r .= '    (' . q($column) . ' = ? OR ' . q($column) . ' IS NULL) AND' . PHP_EOL;
    }

    return rtrim($r, ' AND' . PHP_EOL) . PHP_EOL . PHP_EOL;
}




function chunkSelectQuery(mysqli $db, string $sql, callable $f, array &$items = [], $chunkSize = 5000) {
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


