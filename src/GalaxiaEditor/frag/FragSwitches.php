<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace GalaxiaEditor\frag;

use Galaxia\G;
use Galaxia\Text;
use GalaxiaEditor\E;

class FragSwitches {

    static function render(): void {

        if (!(E::$showSwitchesLang ?? false) && empty(G::$me->perms)) return;

// @formatter:off
?>
<div id="switches">
<?php   if (!empty(G::$me->perms)) { ?>
    <div class="switch-perms">
<?php   foreach (G::$me->perms as $perm) { ?>
        <label for="switch-hide-perm-<?=Text::h($perm)?>" data-remember="true" class="btn btn-pill btn-blue btn-small btn-checkbox active">
            <?=Text::h($perm)?>
            <input type="checkbox" value="hide-active-perm-<?=Text::h($perm)?>" id="switch-hide-perm-<?=Text::h($perm)?>" title="<?=Text::h($perm)?>" checked>
        </label>
<?php   } ?>
    </div>
<?php   } ?>
<?php   if (E::$showSwitchesLang ?? false) { ?>
    <div class="switch-lang">
<?php   foreach (G::locales() as $lang => $locale) { ?>
        <label for="switch-hide-lang-<?=Text::h($lang)?>" data-remember="true" class="btn btn-pill btn-small btn-checkbox active">
            <?=Text::h($lang)?>
            <input type="checkbox" value="hide-active-lang-<?=Text::h($lang)?>" id="switch-hide-lang-<?=Text::h($lang)?>" title="<?=Text::h($lang)?>" checked>
        </label>
<?php       } ?>
    </div>
<?php   } ?>
<?php   if (G::isDev()) { ?>
    <div class="switch-dev">
        <label for="switch-dev-debug" class="btn btn-yellow btn-pill btn-small btn-checkbox<?=Text::h(' active', G::isDevDebug())?>">
            debug
            <input class="ev-cookie-toggle" data-key="<?=Text::h(G::$app->cookieDebugKey)?>" data-val="<?=Text::h(G::$app->cookieDebugVal)?>" type="checkbox" value="notDevDebug" id="switch-dev-debug"<?=Text::h(' checked', G::isDevDebug())?>>
        </label>
    </div>
<?php   } ?>
</div>
<?php
// @formatter:on

    }

}
