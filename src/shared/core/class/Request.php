<?php


namespace Galaxia;


class Request {

    public $host;
    public $uri;

    public $pathOriginal;
    public $path;

    public $query;
    public $scheme;
    public $method;

    public $xhr;
    public $json;

    public $get;
    public $post;

    public $minStatus;
    public $cacheBypass;
    public $cacheBypassHtml;
    public $cacheWrite;


    function __construct(
        string $host,

        string $uri = null,
        string $query = null,
        string $scheme = null,
        string $method = null,

        bool $xhr = null,
        bool $json = null,

        array $get = null,
        array $post = null,

        int $minStatus = null,
        bool $cacheBypass = null,
        bool $cacheBypassHtml = null,
        bool $cacheWrite = null
    ) {
        $this->host = $host;
        $this->uri  = $uri ?? $_SERVER['REQUEST_URI'] ?? '/';
        $this->uri  = urldecode($this->uri);

        $this->pathOriginal = strtok($this->uri, '?');
        $this->path         = gTranslit($this->pathOriginal);

        $this->query  = $query ?? $_SERVER['QUERY_STRING'] ?? '';

        $this->scheme = $scheme ?? $_SERVER['REQUEST_SCHEME'] ?? 'https';
        $this->scheme = in_array($this->scheme, ['http', 'https']) ? $this->scheme : 'https';

        $this->method = $method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->method = in_array($this->method, ['GET', 'POST']) ? $this->method : 'GET';

        $this->xhr = $xhr ?? (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') == 'XMLHttpRequest')    ;
        $this->json = $json ?? (($_SERVER['HTTP_ACCEPT'] ?? '') == 'application/json');

        $this->get  = $get ?? $_GET ?? [];
        $this->post = $post ?? $_POST ?? [];

        $this->minStatus       = 2;
        $this->cacheBypass     = false;
        $this->cacheBypassHtml = false;
        $this->cacheWrite      = true;
    }


    function schemeHost() {
        return $this->scheme . '://' . $this->host;
    }


    function langFromUrl(App $app): string {
        $r = $app->lang;
        foreach ($app->locales as $lang => $locale) {
            if ($this->uri == $locale['url']) {
                $r = $lang;
                break;
            }
            if (substr($this->uri, 0, 4) == $locale['url'] . '/') {
                $r = $lang;
                break;
            }
        }

        return $r;
    }


    function redirectRemoveSlashes() {
        if ($this->path != '/' && substr($this->path, -1, 1) == '/') {
            $this->uri = rtrim($this->path, '/');
            if ($this->uri == '') $this->uri = '/';
            if ($this->query ?? '') $this->uri .= '?' . $this->query;
            header('Location: ' . $this->uri, true, 302);
            exit();
        }
    }


    function redirectTransliterated() {
        if ($this->path != $this->pathOriginal) {
            $this->uri = rtrim($this->path, '/');
            if ($this->uri == '') $this->uri = '/';
            if ($this->query ?? '') $this->uri .= '?' . $this->query;
            header('Location: ' . $this->uri, true, 302);
            exit();
        }

    }


}
