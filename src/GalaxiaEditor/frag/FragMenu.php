<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace GalaxiaEditor\frag;

use Galaxia\G;
use Galaxia\Text;
use GalaxiaEditor\E;

class FragMenu {

    static function render(): void {
        $menu = [];

        foreach (E::$conf as $rootSlug => $confPage) {
            if (!G::isLoggedIn()) continue;
            if (empty($confPage)) continue;

            if ($confPage['gcPageType'] == 'gcpSeparator') {
                $menu[$rootSlug]['cssClass'] = 'menu-separator';
                continue;
            }

            if (!($confPage['gcMenuShow'] ?? '')) continue;

            $menu[$rootSlug]['link']     = '/edit/' . Text::h($rootSlug);
            $menu[$rootSlug]['title']    = $confPage['gcMenuTitle'];
            $menu[$rootSlug]['cssClass'] = '';

            if ($confPage['gcPageType'] == 'gcpLinkToItem') {
                if ($confPage['geLinkToUser'] ?? '') {
                    $menu[$rootSlug]['link'] = '/edit/' . $confPage['geLinkToUser'] . '/' . G::$me->id;

                    if (E::$itemId && E::$pgSlug == $confPage['geLinkToUser'] && E::$itemId == G::$me->id) {
                        $menu[$rootSlug]['cssClass'] .= ' active';
                    }
                } else if ($confPage['geLinkToItem'] ?? '') {
                    $menu[$rootSlug]['link'] = '/edit/' . implode('/', $confPage['geLinkToItem']);

                    if (E::$itemId) {
                        if (E::$pgSlug == $confPage['geLinkToItem'][0] && E::$itemId == $confPage['geLinkToItem'][1]) {
                            $menu[$rootSlug]['cssClass'] .= ' active';
                        }
                    } else if (E::$pgSlug == 'dev' && $confPage['geLinkToItem'][0] == 'dev') {
                        $menu[$rootSlug]['cssClass'] .= ' active';
                    }
                }
            } else {
                if ($rootSlug == E::$pgSlug) $menu[$rootSlug]['cssClass'] .= ' active';
            }


            if (!is_array($confPage['gcMenuShow'])) continue;
            if (!is_array($confPage['gcMenuShow']['gcPerms'])) continue;

            foreach ($confPage['gcMenuShow']['gcPerms'] as $perm) {
                $menu[$rootSlug]['cssClass'] .= ' hide-perm-' . Text::h($perm);
            }
        }

// @formatter:off
?>
<div id="menu" class="non-selectable">
    <a id="website-link" target="_blank" rel="noopener" href="/">
        <img src="/favicon.ico" alt="<?=Text::t('Icon')?>" class="thumb-mini">
        <span><?=Text::h(G::$req->host)?></span>
    </a>

<?php   foreach ($menu as $menuItem) { ?>
    <?php       if (($menuItem['cssClass'] ?? '') == 'menu-separator') { ?>
            <div class="<?=Text::h($menuItem['cssClass'])?>"></div>
    <?php       } else {?>
            <a class="menu-item<?=Text::h($menuItem['cssClass'])?>" href="<?=Text::h($menuItem['link'])?>"><?=Text::t($menuItem['title'])?></a>
    <?php       } ?>
<?php   } ?>

<?php   FragSwitches::render(); ?>
</div>
<?php
// @formatter:on
    }

}
