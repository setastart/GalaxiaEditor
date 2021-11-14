<?php

use Galaxia\Text;
use GalaxiaEditor\E;


$pgTitle = sprintf(Text::t('Delete %s: %s?'), Text::t(E::$conf[$pgSlug]['gcTitleSingle']), Text::h($item['data'][$item['gcColKey']]));
$hdTitle = sprintf(Text::t('Delete %s: %s?'), Text::t(E::$conf[$pgSlug]['gcTitleSingle']), Text::h($item['data'][$item['gcColKey']]));

