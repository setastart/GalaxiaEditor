<?php

use Galaxia\Text;
use GalaxiaEditor\E;


E::$pgTitle = sprintf(Text::t('Delete %s: %s?'), Text::t(E::$section['gcTitleSingle']), Text::h(E::$item['data'][E::$item['gcColKey']]));
E::$hdTitle = sprintf(Text::t('Delete %s: %s?'), Text::t(E::$section['gcTitleSingle']), Text::h(E::$item['data'][E::$item['gcColKey']]));

