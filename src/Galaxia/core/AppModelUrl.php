<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;

class AppModelUrl {

    static function slugRedirectLang(
        string $table,
        int    $minStatus
    ): array {
        $tableId           = $table . 'Id';
        $tableStatus       = $table . 'Status';
        $tableSlug         = $table . 'Slug_';
        $tableRedirect     = $table . 'Redirect';
        $tableRedirectSlug = $table . 'RedirectSlug';

        $r = [];

        $query = Sql::select([
            $table => [$tableId, $tableStatus, $tableSlug],
        ], G::langs());

        $query .= Sql::selectWhere([
            $table => [$tableStatus => '>='],
        ]);

        $result1 = G::execute($query, [$minStatus]);

        while ($data = $result1->fetch_assoc()) {
            foreach (G::langs() as $lang) {
                if (!$data[$tableSlug . $lang]) continue;
                $r[$lang][$data[$tableSlug . $lang]] = $data[$tableId];
            }
        }

        $query = Sql::select([
            $tableRedirect => [$tableRedirectSlug, $tableId],
        ], G::langs());

        $query .= Sql::selectLeftJoinUsing([
            $table => [$tableId],
        ]);

        $query .= Sql::selectWhere([
            $table => [$tableStatus => '>='],
        ]);

        $result2 = G::execute($query, [$minStatus]);

        while ($data = $result2->fetch_assoc()) {
            if (!$data[$tableRedirectSlug]) continue;
            $r[$tableRedirect][$data[$tableRedirectSlug]] = $data[$tableId];
        }

        return $r;
    }

    static function slugRedirect(
        string $table,
        int $minStatus
    ): array {
        $tableId           = $table . 'Id';
        $tableStatus       = $table . 'Status';
        $tableSlug         = $table . 'Slug';
        $tableRedirect     = $table . 'Redirect';
        $tableRedirectSlug = $table . 'RedirectSlug';

        $r = [];

        $query = Sql::select([
            $table => [$tableId, $tableStatus, $tableSlug],
        ]);

        $query .= Sql::selectWhere([
            $table => [$tableStatus => '>='],
        ]);

        $result1 = G::execute($query, [$minStatus]);

        while ($data = $result1->fetch_assoc()) {
            if (!$data[$tableSlug]) continue;
            $r[$data[$tableSlug]] = $data[$tableId];
        }

        $query = Sql::select([
            $tableRedirect => [$tableRedirectSlug, $tableId],
        ]);

        $query .= Sql::selectLeftJoinUsing([
            $table => [$tableId],
        ]);

        $query .= Sql::selectWhere([
            $table => [$tableStatus => '>='],
        ]);

        $result2 = G::execute($query, [$minStatus]);

        while ($data = $result2->fetch_assoc()) {
            if (!$data[$tableRedirectSlug]) continue;
            $r[$tableRedirect][$data[$tableRedirectSlug]] = $data[$tableId];
        }

        return $r;
    }

}
