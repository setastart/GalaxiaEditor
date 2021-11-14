<?php

use Galaxia\G;
use Galaxia\Text;


$pgTitle = sprintf(Text::t('Delete %s: %s?'), Text::t(G::$conf[$pgSlug]['gcTitleSingle']), Text::h($item['data'][$item['gcColKey']]));
$hdTitle = sprintf(Text::t('Delete %s: %s?'), Text::t(G::$conf[$pgSlug]['gcTitleSingle']), Text::h($item['data'][$item['gcColKey']]));

