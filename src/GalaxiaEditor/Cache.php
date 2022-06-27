<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace GalaxiaEditor;


use Galaxia\G;

class Cache {

    static function route(): string {
        return G::$app->dirCache . 'editor/editor-99-fastroute-' . G::$me->id;
    }

    static function config(callable $f): array {
        return G::cacheArray('editor', 1, 'config', $f, G::$req->cacheBypass);
    }

    static function imageListItems(callable $f): array {
        return G::cacheArray('editor', 2, 'imageList-' . E::$pgSlug . '-items', $f, G::$req->cacheBypass);
    }

    static function imageListRowsSelect(callable $f): array {
        return G::cacheArray('editor', 3, 'imageList-' . E::$pgSlug . '-rows-select', $f, G::$req->cacheBypass);
    }

    static function imageListRows(callable $f): array {
        return G::cacheArray('editor', 3, 'imageList-' . E::$pgSlug . '-rows', $f, G::$req->cacheBypass);
    }

    static function imageListFilterText(string $filterId, callable $f): array {
        return G::cacheArray('editor', 3, 'imageList-' . E::$pgSlug . '-filterTexts-' . $filterId, $f, G::$req->cacheBypass);
    }

    static function imageListInUse(callable $f): array {
        return G::cacheArray('editor', 2, 'imageList-' . E::$pgSlug . '-inUse', $f, G::$req->cacheBypass);
    }




    static function listItems(string $order, callable $f): array {
        return G::cacheArray('editor', 2, 'list-' . $order . E::$pgSlug . '-items', $f, G::$req->cacheBypass);
    }

    static function listRows(string $order, callable $f): array {
        return G::cacheArray('editor', 3, 'list-' . $order . E::$pgSlug . '-rows', $f, G::$req->cacheBypass);
    }

    static function listItemsFilterInt(string $filterId, callable $f): array {
        return G::cacheArray('editor', 3, 'list-' . E::$pgSlug . '-filterInt-' . $filterId,  $f, G::$req->cacheBypass);
    }

    static function listItemsFilterText(string $filterId, callable $f): array {
        return G::cacheArray('editor', 4, 'list-' . E::$pgSlug . '-filterText-' . $filterId, $f, G::$req->cacheBypass);
    }

    static function itemList(callable $f): array {
        return G::cacheArray('editor', 2, 'item-' . E::$pgSlug . '-items', $f, G::$req->cacheBypass);
    }




    static function historyItems(callable $f): array {
        return G::cacheArray('editor', 2, 'historyList-' . E::$pgSlug . '-items', $f, G::$req->cacheBypass);
    }

    static function historyRows(callable $f): array {
        return G::cacheArray('editor', 3, 'historyList-' . E::$pgSlug . '-rows', $f, G::$req->cacheBypass);
    }

}
