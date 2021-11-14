<?php


namespace GalaxiaEditor\model;

use Galaxia\G;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\E;


class ModelImage {

    static function inUse(): array {
        $inUse = [];
        foreach (E::$section['gcImagesInUse'] as $gcImageInUse) {

            if (empty($gcImageInUse['gcSelect'])) return [];
            $firstTable  = key($gcImageInUse['gcSelect']);
            $firstColumn = $gcImageInUse['gcSelect'][$firstTable][0] ?? [];

            $query = Sql::select($gcImageInUse['gcSelect']);
            $query .= Sql::selectLeftJoinUsing($gcImageInUse['gcSelectLJoin'] ?? []);
            $query .= Sql::selectOrderBy($gcImageInUse['gcSelectOrderBy'] ?? []);

            $stmt = G::prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($data = $result->fetch_assoc()) {
                $data = array_map('strval', $data);

                foreach ($gcImageInUse['gcSelect'] as $table => $columns) {
                    if ($table == $firstTable) {

                        $found = [];
                        foreach ($columns as $column) {
                            if ($column == $firstColumn) continue;

                            if (substr($column, -3, 1) == '_') {
                                $canonical = substr($column, 0, -2);
                                if (empty($data[$column])) continue;
                                if (in_array($canonical, $found)) continue;
                                $found[] = substr($column, 0, -2);
                            }

                            if (empty($data[$column])) {
                                $inUse[$data[$firstColumn]][$table] = '<span class="small red">' . Text::t('Empty') . '</span>';
                            } else {
                                $inUse[$data[$firstColumn]][$table] = Text::h($data[$column]);
                            }
                        }

                    } else {

                        $found = [];
                        foreach ($columns as $column) {
                            if (isset($inUse[$data[$firstColumn]][$table]))
                                if (isset($inUse[$data[$firstColumn]][$table]))
                                    if (in_array($data[$column], $inUse[$data[$firstColumn]][$table])) continue;

                            if (substr($column, -3, 1) == '_') {
                                $canonical = substr($column, 0, -2);
                                if (empty($data[$column])) continue;
                                if (in_array($canonical, $found)) continue;
                                $found[] = substr($column, 0, -2);
                            }

                            if (empty($data[$column])) {
                                $inUse[$data[$firstColumn]][$table][] = '<span class="small red">' . Text::t('Empty') . '</span>';
                            } else {
                                $inUse[$data[$firstColumn]][$table][] = Text::h($data[$column]);
                            }
                        }

                    }
                }
            }
            $stmt->close();
        }

        return $inUse;

    }

}
