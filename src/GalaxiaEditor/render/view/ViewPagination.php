<?php


namespace GalaxiaEditor\render\view;


use Galaxia\Pagination;
use Galaxia\Text;


class ViewPagination {

    public static function renderHeader(Pagination $pagination): void {
        if (!$pagination->active) return;

// @formatter:off ?>
    <div class="pagination btn-row pad">
        <label class="input-label" for="itemsPerPage"><?=Text::t('Per page')?></label>
        <div class="btn-group space-rgt">
            <input class="itemsPerPage" id="itemsPerPage" type="number" min="1" name="itemsPerPage" value="<?=$pagination->itemsPerPage?>">
        </div>
        <label class="input-label" for="pageCurrent"><?=Text::t('Page')?></label>
        <div class="btn-group">
            <input class="pageCurrent" id="pageCurrent" type="number" min="1" name="page" value="<?=$pagination->pageCurrent?>">
        </div>
        <div class="space-rgt"> &nbsp;&#8725;&nbsp; <span class="pageLast"><?=$pagination->pageLast?></span></div>
        <div class="btn-group">
            <button name="page" value="<?=$pagination->pageFirst?>" class="pageFirst btn-new pagination-first active" <?=($pagination->pageFirst) ?: 'disabled'?>></button>
        </div>
        <div class="btn-group">
            <button name="page" value="<?=$pagination->pagePrev?>" class="pagePrev btn-new pagination-prev active" <?=($pagination->pagePrev) ?: 'disabled'?>></button>
        </div>
        <div class="btn-group">
            <button name="page" value="<?=$pagination->pageNext?>" class="pageNext btn-new pagination-next active" <?=($pagination->pageNext) ?: 'disabled'?>></button>
        </div>
        <div class="btn-group">
            <button name="page" value="<?=$pagination->pageLast?>" class="pageLast btn-new pagination-last active" <?=($pagination->pageCurrent < $pagination->pageLast) ?: 'disabled'?>></button>
        </div>
    </div>
<?php // @formatter:on

    }




    public static function renderFooter(Pagination $pagination): void {
        if (!$pagination->active) return;

// @formatter:off ?>
    <div class="pagination pagination-footer btn-row pad">
        <div class="btn-group">
            <button name="page" value="<?=$pagination->pageFirst?>" class="pageFirst btn-new pagination-first active" <?=($pagination->pageFirst) ?: 'disabled'?>></button>
        </div>
        <div class="btn-group space-rgt">
            <button name="page" value="<?=$pagination->pagePrev?>" class="pagePrev btn-new pagination-prev active" <?=($pagination->pagePrev) ?: 'disabled'?>></button>
        </div>

        <div class="space-rgt"><?=Text::t('Page')?>&nbsp;<span class="pageCurrent"><?=$pagination->pageCurrent?></span> &nbsp;&#8725;&nbsp; <span class="pageLast"><?=$pagination->pageLast?></span></div>

        <div class="btn-group">
            <button name="page" value="<?=$pagination->pageNext?>" class="pageNext btn-new pagination-next active" <?=($pagination->pageNext) ?: 'disabled'?>></button>
        </div>
        <div class="btn-group">
            <button name="page" value="<?=$pagination->pageLast?>" class="pageLast btn-new pagination-last active" <?=($pagination->pageCurrent < $pagination->pageLast) ?: 'disabled'?>></button>
        </div>
    </div>
<?php // @formatter:on
    }




    public static function renderHiddenData(Pagination $pagination, int $rowsFiltered, int $rowsTotal): void {

// @formatter:off ?>
    <div class="results hide"
         data-pageCurrent="<?=$pagination->pageCurrent?>"
         data-pageFirst="<?=$pagination->pageFirst?>"
         data-pagePrev="<?=$pagination->pagePrev?>"
         data-pageNext="<?=$pagination->pageNext?>"
         data-pageLast="<?=$pagination->pageLast?>"
         data-rowsFiltered="<?=$rowsFiltered?>"
         data-rowsTotal="<?=$rowsTotal?>"
    ></div>
<?php // @formatter:on
    }

}