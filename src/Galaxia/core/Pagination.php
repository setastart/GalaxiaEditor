<?php
/* Copyright 2017-2021 Ino Detelić & Zaloa G. Ramos

 - Licensed under the EUPL, Version 1.2 only (the "Licence");
 - You may not use this work except in compliance with the Licence.

 - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

 - Unless required by applicable law or agreed to in writing, software distributed
   under the Licence is distributed on an "AS IS" basis,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 - See the Licence for the specific language governing permissions and limitations under the Licence.
*/

namespace Galaxia;


class Pagination {

    public bool $active = false;

    // pages
    public int $pageCurrent = 1;
    public int $pagePrev    = 0;
    public int $pageNext    = 0;
    public int $pageFirst   = 0;
    public int $pageLast    = 0;

    // items
    public int $itemsPerPage = 50;
    public int $itemsTotal   = 0;
    public int $itemFirst    = 1;


    public function __construct(int $pageCurrent, int $itemsPerPage = null) {
        $this->pageCurrent = $pageCurrent;
        if ($itemsPerPage !== null) $this->itemsPerPage = $itemsPerPage;

    }


    public function setItemsTotal(int $itemsTotal): void {
        $this->itemsTotal = $itemsTotal;
        $this->compute();
    }


    public function compute(): void {

        $this->pageLast = ceil($this->itemsTotal / $this->itemsPerPage);
        if ($this->pageLast < 1)
            $this->pageLast = 1;

        if ($this->pageCurrent < 1)
            $this->pageCurrent = 1;

        if ($this->pageCurrent > $this->pageLast)
            $this->pageCurrent = $this->pageLast;

        if ($this->pageCurrent < $this->pageLast)
            $this->pageNext = $this->pageCurrent + 1;

        if ($this->pageCurrent > 1) {
            $this->pagePrev  = $this->pageCurrent - 1;
            $this->pageFirst = 1;
        }


        $this->itemFirst = ($this->itemsPerPage * ($this->pageCurrent - 1)) + 1;

        if ($this->itemsTotal > $this->itemsPerPage) $this->active = true;
    }


    // public function sliceRows(array $rows): array {
    //     $offset = $this->itemFirst - 1;
    //     $length = $this->itemsPerPage;
    //     if ($length >= $this->itemsTotal) $length = null;
    //
    //     return array_slice($rows, $offset, $length);
    // }

}




