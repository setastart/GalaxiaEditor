<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace GalaxiaEditor\frag;

use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Text;

class FragMsgBoxes {

    static function render(): void {

// @formatter:off
?>
<div id="msgboxes">
<?php   if (Flash::hasError('errorBox')) { ?>
    <ul id="msgbox-error" class="msgbox pad">
        <div class="msgbox-title"><?=Text::t('Errors')?>:</div>
<?php       foreach (Flash::errors('errorBox') as $log) { ?>
<?php           if (is_array($log)) continue; ?>
        <li><?=$log?></li>
<?php       } ?>
    </ul>
<?php   } ?>
<?php   if (Flash::hasWarning('warningBox')) { ?>
    <ul id="msgbox-warning" class="msgbox pad">
        <div class="msgbox-title"><?=Text::t('Warnings')?>:</div>
<?php       foreach (Flash::warnings('warningBox') as $log) { ?>
<?php           if (is_array($log)) continue; ?>
        <li><?=$log?></li>
<?php       } ?>
    </ul>
<?php   } ?>
<?php   if (Flash::hasInfo('infoBox')) { ?>
    <ul id="msgbox-info" class="msgbox pad">
        <div class="msgbox-title"><?=Text::t('Information')?>:</div>
<?php       foreach (Flash::infos('infoBox') as $log) { ?>
<?php           if (is_array($log)) continue; ?>
        <li><?=$log?></li>
<?php       } ?>
    </ul>
<?php   } ?>
<?php   if (Flash::hasDevlog('devlogBox') && G::isDev()) { ?>
    <ul id="msgbox-devlog" class="msgbox pad">
        <div class="msgbox-title"><?=Text::t('Devlog')?>:</div>
<?php       foreach (Flash::devlogs('devlogBox') as $log) { ?>
<?php           if (is_array($log)) continue; ?>
        <li><?=$log?></li>
<?php       } ?>
    </ul>
<?php   } ?>
</div>
<?php
// @formatter:on
    }

}
