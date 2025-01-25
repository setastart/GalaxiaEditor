<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


use function strlen;

class Request { // todo: rename to AppRequest

    public string $pathOriginal;
    public string $path;

    public array $vars;

    public int      $pagId;
    public bool     $isRoot;
    public string   $type;
    public string   $route;
    public bool|int $redirectId;


    function __construct(
        public string  $host,

        public ?string $uri = null,
        public ?string $query = null,
        public ?string $scheme = null,
        public ?string $method = null,

        public ?bool   $test = null,
        public ?bool   $xhr = null,
        public ?bool   $json = null,

        public ?array  $get = null,
        public ?array  $post = null,
        public ?array  $cookie = null,

        public ?int    $minStatus = null,
        public ?bool   $cacheBypass = null,
        public ?bool   $cacheBypassHtml = null,
        public ?bool   $cacheWrite = null
    ) {
        $this->uri = $uri ?? $_SERVER['REQUEST_URI'] ?? '/';
        $this->uri = urldecode($this->uri);

        $this->pathOriginal = strtok($this->uri, '?');
        $this->path         = Text::translit($this->pathOriginal);

        $this->query = $query ?? $_SERVER['QUERY_STRING'] ?? '';

        $this->scheme = $scheme ?? $_SERVER['REQUEST_SCHEME'] ?? 'https';
        $this->scheme = in_array($this->scheme, ['http', 'https']) ? $this->scheme : 'https';

        $this->method = $method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->method = in_array($this->method, ['GET', 'POST']) ? $this->method : 'GET';

        $this->test = $test ?? (($_SERVER['HTTP_X_GALAXIAEDITOR_TEST'] ?? '') == '1');
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
            if (substr($this->uri, 0, 4) == $locale['url']) {
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
            $location = Text::h(trim($this->path, "/ \t\n\r\0\x0B"));

            if (G::isCli()) {
                echo "301 - /$location" . PHP_EOL;
                exit();
            }

            if (headers_sent()) {
                echo 'headers already sent. redirect: <a href="' . $location . '">' . $location . '</a>' . PHP_EOL;
                exit();
            }

            header('Location: /' . $location, true, 301);
            exit();
        }
    }


    function redirectRemoveAddSlashes(): void {
        if (strlen($this->path) == 4 && str_ends_with($this->path, '/')) return;
        if (strlen($this->path) == 3 && !str_ends_with($this->path, '/')) {
            $location = Text::h(trim($this->path, "/ \t\n\r\0\x0B"));
            if (strlen($location) == 2) $location .= '/';

            if (G::isCli()) {
                echo "301 - /$location" . PHP_EOL;
                exit();
            }

            if (headers_sent()) {
                echo 'headers already sent. redirect: <a href="' . $location . '">' . $location . '</a>' . PHP_EOL;
                exit();
            }

            header('Location: /' . $location, true, 301);
            exit();
        }
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
