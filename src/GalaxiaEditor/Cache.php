<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace GalaxiaEditor;


use Galaxia\AppCache;
use Galaxia\G;

class Cache {

    static function route(): string {
        return G::$app->dirCache . 'editor/editor-99-fastroute-' . G::$me->id . '.cache';
    }

    static function config(callable $f): array {
        return self::array(1, 'config-' . G::$me->id, $f);
    }

    static function imageListItems(callable $f): array {
        return self::array(2, 'imageList-' . E::$pgSlug . '-items', $f);
    }

    static function imageListRowsSelect(callable $f): array {
        return self::array(3, 'imageList-' . E::$pgSlug . '-rows-select', $f);
    }

    static function imageListRows(callable $f): array {
        return self::array(3, 'imageList-' . E::$pgSlug . '-rows', $f);
    }

    static function imageListFilterText(string $filterId, callable $f): array {
        return self::array(3, 'imageList-' . E::$pgSlug . '-filterTexts-' . $filterId, $f);
    }

    static function imageListInUse(callable $f): array {
        return self::array(2, 'imageList-' . E::$pgSlug . '-inUse', $f);
    }




    static function listItems(string $order, callable $f): array {
        return self::array(2, 'list-' . $order . E::$pgSlug . '-items', $f);
    }

    static function listRows(string $order, callable $f): array {
        return self::array(3, 'list-' . $order . E::$pgSlug . '-rows', $f);
    }

    static function listItemsFilterInt(string $filterId, callable $f): array {
        return self::array(3, 'list-' . E::$pgSlug . '-filterInt-' . $filterId,  $f);
    }

    static function listItemsFilterText(string $filterId, callable $f): array {
        return self::array(4, 'list-' . E::$pgSlug . '-filterText-' . $filterId, $f);
    }

    static function itemList(callable $f): array {
        return self::array(2, 'item-' . E::$pgSlug . '-items', $f);
    }




    static function historyItems(callable $f): array {
        return self::array(2, 'historyList-' . E::$pgSlug . '-items', $f);
    }

    static function historyRows(callable $f): array {
        return self::array(3, 'historyList-' . E::$pgSlug . '-rows', $f);
    }


    static function array(int $level, string $key, callable $f): array {
        return AppCache::cacheArray(
            scope: 'editor',
            level:  $level,
            key: $key,
            f: $f,
            load: !G::$req->cacheBypass
        );
    }
}
