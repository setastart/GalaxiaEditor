<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;

use DateTime;

class AppMeta {

    public bool   $index   = true;
    public string $editUrl = '';
    public int    $status  = 2;

    public string   $tsCreate = '';
    public string   $tsModify = '';
    public DateTime $dtCreate;
    public DateTime $dtModify;

    public array  $title     = [];
    public array  $menu      = [];
    public array  $subtitle  = [];
    public string $titleHead = '';
    public array  $url       = [];

    public array $desc   = [];
    public array $images = [];

    public string $ogUrl    = '';
    public string $ogImage  = '';
    public string $ogLocale = '';

    public array  $schemaOrg = [];
    public string $version   = '';
    public string $preload   = '';

    public function __construct() {
        $this->version = G::versionQuery();
        $this->ogImage = G::$req->schemeHost() . '/favicon.png';
        if (G::$req->post) $this->index = false;
    }

    function fromPag(
        array  $pag,
        array  $pagCon,
        string $siteSuffix,
        string $pagStatus = 'pageStatus',
        string $pagUrl = 'pageUrl_',
        string $pagTitle = 'pageTitle_',
        string $pagMenu = 'pageMenu_',
        string $pagSubtitle = 'pageSubtitle_',
        string $pagContentTable = 'pageContent',
        string $pagContentHeadTitle = 'headTitle',
        string $pagContentMetaDescription = 'metaDescription',
        string $pagContentContent = 'content',
    ): void {
        $this->status   = $pag[$pagStatus] ?? 2;
        $this->tsCreate = $pag[C::created] ?? '';
        $this->tsModify = $pag[C::modified] ?? '';
        $this->dtCreate = date_create('@' . $this->tsCreate) ?: date_create();
        $this->dtModify = date_create('@' . $this->tsModify) ?: date_create();

        $this->title    = $pag[$pagTitle] ?? [];
        $this->menu     = $pag[$pagMenu] ?? $pag[$pagTitle] ?? [];
        $this->subtitle = $pag[$pagSubtitle] ?? [];

        $this->titleHead = Text::hg($pag[$pagContentTable][$pagContentHeadTitle] ?? $pag[$pagTitle] ?? $this->title) ?? '';
        if ($siteSuffix) {
            $this->titleHeadAddSuffix($siteSuffix);
        }

        $this->url = $pag[$pagUrl] ?? [];

        $this->desc = $pagCon[$pagContentMetaDescription] ?? $pagCon[$pagContentContent] ?? [];

        $this->ogUrl    = Text::h(G::$req->schemeHost() . Text::hg($this->url)) ?? '';
        $this->ogLocale = G::locale()['long'];

        if (G::isLoggedIn()) $this->editUrl = '/edit/page/' . G::$req->pagId;
    }


    private function titleHeadAddSuffix(string $suffix = null): void {
        if (!str_contains($this->titleHead, $suffix)) {
            $this->titleHead .= ' - ' . $suffix;
        }
    }


}
