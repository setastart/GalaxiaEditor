<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


class Request {

    public string $host;
    public string $uri;

    public string $pathOriginal;
    public string $path;

    public string $query;
    public string $scheme;
    public string $method;

    public bool $xhr;
    public bool $json;

    public array $get;
    public array $post;
    public array $cookie;

    public int  $minStatus;
    public bool $cacheBypass;
    public bool $cacheBypassHtml;
    public bool $cacheWrite;

    public int      $pagId;
    public bool     $isRoot;
    public string   $route;
    public bool|int $redirectId;
    public array    $vars;


    function __construct(
        string $host,

        string $uri = null,
        string $query = null,
        string $scheme = null,
        string $method = null,

        bool   $xhr = null,
        bool   $json = null,

        array  $get = null,
        array  $post = null,
        array  $cookie = null,

        int    $minStatus = null,
        bool   $cacheBypass = null,
        bool   $cacheBypassHtml = null,
        bool   $cacheWrite = null
    ) {
        $this->host = $host;
        $this->uri  = $uri ?? $_SERVER['REQUEST_URI'] ?? '/';
        $this->uri  = urldecode($this->uri);

        $this->pathOriginal = strtok($this->uri, '?');
        $this->path         = Text::translit($this->pathOriginal);

        $this->query = $query ?? $_SERVER['QUERY_STRING'] ?? '';

        $this->scheme = $scheme ?? $_SERVER['REQUEST_SCHEME'] ?? 'https';
        $this->scheme = in_array($this->scheme, ['http', 'https']) ? $this->scheme : 'https';

        $this->method = $method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->method = in_array($this->method, ['GET', 'POST']) ? $this->method : 'GET';

        $this->xhr  = $xhr ?? (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') == 'XMLHttpRequest');
        $this->json = $json ?? (($_SERVER['HTTP_ACCEPT'] ?? '') == 'application/json');

        $this->get    = $get ?? $_GET ?? [];
        $this->post   = $post ?? $_POST ?? [];
        $this->cookie = $cookie ?? $_COOKIE ?? [];

        $this->minStatus       = $minStatus ?? 2;
        $this->cacheBypass     = $cacheBypass ?? false;
        $this->cacheBypassHtml = $cacheBypassHtml ?? false;
        $this->cacheWrite      = $cacheWrite ?? true;
    }


    function setUri(string $uri): void {
        $this->uri          = $uri;
        $this->uri          = urldecode($this->uri);
        $this->pathOriginal = strtok($this->uri, '?');
        $this->path         = Text::translit($this->pathOriginal);
    }


    function isHttps(): bool {
        return $this->scheme == 'https';
    }


    function schemeHost(): string {
        return $this->scheme . '://' . $this->host;
    }


    function langFromUrl(): string {
        foreach (G::$app->locales as $lang => $locale) {
            if ($this->uri == $locale['url']) {
                return $lang;
            }
            if (substr($this->uri, 0, 4) == $locale['url'] . '/') {
                return $lang;
            }
        }

        return G::$app->lang;
    }


    function redirectRemoveSlashes(): void {
        if ($this->path != '/' && str_ends_with($this->path, '/')) {
            G::redirect($this->path, 301);
        }
    }


    function redirectRemoveQuery(): void {
        if ($this->query) {
            G::redirect($this->path, 301);
        }
    }


    function redirectTransliterated(): void {
        if ($this->path != $this->pathOriginal) {
            G::redirect($this->path, 301);
        }
    }

}
