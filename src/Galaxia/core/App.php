<?php
/* Copyright 2017-2020 Ino Detelić & Zaloa G. Ramos

 - Licensed under the EUPL, Version 1.2 only (the "Licence");
 - You may not use this work except in compliance with the Licence.

 - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

 - Unless required by applicable law or agreed to in writing, software distributed
   under the Licence is distributed on an "AS IS" basis,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 - See the Licence for the specific language governing permissions and limitations under the Licence.
*/

namespace Galaxia;


use Exception;


class App {

    /**
     * @deprecated
     */
    public string $version = '2020';

    public string $dir       = '';
    public string $dirLog    = '';
    public string $dirCache  = '';
    public string $dirImage  = '';
    public string $urlImages = '/media/image/';

    public array $routes = [];

    /**
     * @deprecated
     */
    public int $pageId = 0;

    /**
     * @deprecated
     */
    public bool $pageIsRoot = false;

    /**
     * @deprecated
     */
    public ?array $pagesById = null;

    public array $locale  = ['url' => '/', 'long' => 'en_US', 'full' => 'English'];
    public array $locales = [
        'en' => ['url' => '/', 'long' => 'en_US', 'full' => 'English'],
    ];

    public array $localesInactive = [];

    public array  $langs    = ['en'];
    public string $lang     = 'en';
    public string $timeZone = 'Europe/Lisbon';

    public string $cookieEditorKey           = 'galaxiaEditor';
    public string $cookieNginxCacheBypassKey = '';
    public string $cookieDebugKey            = 'debug';
    public string $cookieDebugVal            = '';

    public string $mysqlHost = '127.0.0.1';
    public string $mysqlDb   = '';
    public string $mysqlUser = '';
    public string $mysqlPass = '';

    /**
     * @deprecated
     */
    public bool $cacheBypass = false;

    public function __construct(string $dir) {
        $this->dir      = rtrim($dir, '/') . '/';
        $this->dirCache = $this->dir . 'var/cache/';
        $this->dirLog   = $this->dir . 'var/log/';
        $this->dirImage = $this->dir . 'var/media/image/';
    }




    // locale

    /**
     * @deprecated
     */
    public function localeSetupFromUrl(): void {
        if (isset($_SERVER['REQUEST_URI'])) {
            foreach ($this->locales as $lang => $locale) {
                if ($_SERVER['REQUEST_URI'] == $locale['url']) {
                    $this->lang = $lang;
                    break;
                }
                if (substr($_SERVER['REQUEST_URI'], 0, 4) == $locale['url'] . '/') {
                    $this->lang = $lang;
                    break;
                }
            }
        }
        $this->setLang($this->lang);
    }


    public function setLang(string $lang = null): void {
        if (!is_string($lang) || !isset($this->locales[$lang])) $lang = $this->lang;
        $this->lang   = $lang;
        $this->locale = $this->locales[$this->lang];
        $this->langs  = array_keys($this->locales);
        $key          = array_search($this->lang, $this->langs);
        if ($key > 0) {
            unset($this->langs[$key]);
            array_unshift($this->langs, $this->lang);
        }

        setlocale(LC_TIME, $this->locale['long'] . '.UTF-8');
        date_default_timezone_set($this->timeZone);
    }


    public function addLangPrefix(string $url, string $lang = '') {
        $url = trim($url, '/');
        if (!isset($this->locales[$lang])) $lang = $this->lang;
        if ($url == '') return $this->locales[$lang]['url'];

        return Text::h(rtrim($this->locales[$lang]['url'], '/') . '/' . $url);
    }




    // default page, slug, route loading

    /**
     * @deprecated
     */
    function loadPagesById(
        string $id = 'id', string $status = 'status', string $type = 'type',
        string $slug = 'slug', string $title = 'title', string $url = 'url',
        string $cacheKey = 'loadPagesById'
    ) {
        if ($this->pagesById != null) return;
        $this->pagesById = $this->cacheGet('app', 1, $cacheKey, function() use ($id, $status, $type, $slug, $title, $url) {
            $app       = Director::getApp();
            $db        = Director::getMysqli();
            $pagesById = [];
            $query     = Sql::select(['page' => ['pageId', 'pageStatus', 'pageSlug_', 'pageTitle_', 'pageType']], $app->langs);
            $query     .= 'WHERE pageStatus > 1' . PHP_EOL;
            // dd($query);
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($data = $result->fetch_assoc()) {
                if (!isset($app->routes[$data['pageType']])) continue;

                $pagesById[$data['pageId']][$id]     = $data['pageId'];
                $pagesById[$data['pageId']][$status] = $data['pageStatus'];
                $pagesById[$data['pageId']][$type]   = $data['pageType'];

                foreach ($app->langs as $lang) {
                    $pagesById[$data['pageId']][$slug][$lang]  = $data['pageSlug_' . $lang];
                    $pagesById[$data['pageId']][$title][$lang] = $data['pageTitle_' . $lang];
                    $pagesById[$data['pageId']][$url][$lang]   = $app->addLangPrefix($data['pageSlug_' . $lang], $lang);
                }
            }
            $stmt->close();

            return $pagesById;
        });
    }


    /**
     * @deprecated
     */
    function loadPagesByIdDraft(
        string $id = 'id', string $status = 'status', string $type = 'type',
        string $slug = 'slug', string $title = 'title', string $url = 'url',
        string $cacheKey = 'loadPagesByIdDraft'
    ) {
        $pagesByIdDraft  = $this->cacheGet('app', 1, $cacheKey, function() use ($id, $status, $type, $slug, $title, $url) {
            $app = Director::getApp();
            $db  = Director::getMysqli();

            $pagesByIdDraft = [];
            $query          = Sql::select(['page' => ['pageId', 'pageStatus', 'pageSlug_', 'pageTitle_', 'pageType']], $app->langs);
            $query          .= 'WHERE pageStatus = 1' . PHP_EOL;
            // dd($query);
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($data = $result->fetch_assoc()) {
                if (!isset($app->routes[$data['pageType']])) continue;

                $pagesByIdDraft[$data['pageId']][$id]     = $data['pageId'];
                $pagesByIdDraft[$data['pageId']][$status] = $data['pageStatus'];
                $pagesByIdDraft[$data['pageId']][$type]   = $data['pageType'];

                foreach ($app->langs as $lang) {
                    $pagesByIdDraft[$data['pageId']][$slug][$lang]  = $data['pageSlug_' . $lang];
                    $pagesByIdDraft[$data['pageId']][$title][$lang] = $data['pageTitle_' . $lang];
                    $pagesByIdDraft[$data['pageId']][$url][$lang]   = $app->addLangPrefix($data['pageSlug_' . $lang], $lang);
                }
            }
            $stmt->close();

            return $pagesByIdDraft;
        });
        $this->pagesById += $pagesByIdDraft;
    }


    function defaultRoutes(int $pageMinStatus, $pageSlug = 'pgSlug') {
        $routes                  = [];
        $routesVisited           = [];
        $slugsAndRedirectsByType = [];

        $db = Director::getMysqli();

        $query = Sql::select([
            'page'         => ['pageId', 'pageStatus', 'pageSlug_', 'pageType'],
            'pageRedirect' => ['pageRedirectId', 'pageRedirectSlug'],
        ], $this->langs);

        $query .= Sql::selectLeftJoinUsing(['pageRedirect' => ['pageId']]);

        $query .= 'WHERE pageStatus >= ' . $pageMinStatus . PHP_EOL;

        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($data = $result->fetch_assoc()) {
            if (!isset($this->routes[$data['pageType']])) continue;

            foreach ($this->langs as $lang)
                $slugsAndRedirectsByType['slugs'][$data['pageType']][$data['pageId']][$lang] = $data['pageSlug_' . $lang];

            if ($data['pageRedirectSlug'])
                $slugsAndRedirectsByType['redirects'][$data['pageType']][$data['pageId']][$data['pageRedirectId']] = $data['pageRedirectSlug'];
        }
        $stmt->close();


        // main lang
        foreach ($this->routes as $pageType => $patterns) {
            if (!isset($slugsAndRedirectsByType['slugs'][$pageType])) continue;
            foreach ($patterns as $pattern => $methods) {
                foreach ($slugsAndRedirectsByType['slugs'][$pageType] as $pageId => $page) {
                    foreach ($methods as $routeMethod => $route) {
                        foreach ($page as $lang => $slug) {
                            if ($slug == '') $routeFinal = $this->locales[$lang]['url'] . '{' . $pageSlug . ':' . $slug . '}' . $pattern;
                            else $routeFinal = (($this->locales[$lang]['url'] == '/') ? '/' : $this->locales[$lang]['url'] . '/') . '{' . $pageSlug . ':' . $slug . '}' . $pattern;
                            $routeMeta = [
                                'template' => $route['template'],
                                'pageId'   => $pageId,
                                'isRoot'   => empty($pattern),
                                'redirect' => false,
                            ];
                            if (!isset($routesVisited[$routeFinal][$routeMethod])) {
                                $routes[] = ['method' => $routeMethod, 'route' => $routeFinal, 'meta' => $routeMeta];

                                $routesVisited[$routeFinal][$routeMethod] = true;
                            }
                        }
                    }
                }
            }
        }

        // page redirects
        foreach ($this->routes as $pageType => $patterns) {
            if (!isset($slugsAndRedirectsByType['redirects'][$pageType])) continue;
            foreach ($patterns as $pattern => $methods) {
                foreach ($slugsAndRedirectsByType['redirects'][$pageType] as $pageId => $redirect) {
                    foreach ($methods as $routeMethod => $route) {
                        if ($routeMethod != 'GET') continue;
                        foreach ($redirect as $redirectId => $slug) {
                            if (!$slug) continue;
                            foreach ($this->langs as $lang) {
                                $routeFinal = (($this->locales[$lang]['url'] == '/') ? '/' : $this->locales[$lang]['url'] . '/') . '{' . $pageSlug . ':' . $slug . '}' . $pattern;
                                $routeMeta  = [
                                    'template' => $route['template'],
                                    'pageId'   => $pageId,
                                    'isRoot'   => empty($pattern),
                                    'redirect' => $redirectId,
                                ];
                                if (!isset($routesVisited[$routeFinal][$routeMethod])) {
                                    $routes[] = ['method' => $routeMethod, 'route' => $routeFinal, 'meta' => $routeMeta];

                                    $routesVisited[$routeFinal][$routeMethod] = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        // secondary langs
        foreach ($this->routes as $pageType => $patterns) {
            if (!isset($slugsAndRedirectsByType['slugs'][$pageType])) continue;
            foreach ($patterns as $pattern => $methods) {
                foreach ($slugsAndRedirectsByType['slugs'][$pageType] as $pageId => $page) {
                    foreach ($methods as $routeMethod => $route) {
                        if ($routeMethod != 'GET') continue;
                        foreach ($page as $lang => $slug) {
                            foreach ($this->langs as $lang2) {
                                if ($lang2 == $lang) continue;
                                if (!$slug) continue;
                                $routeFinal = (($this->locales[$lang2]['url'] == '/') ? '/' : $this->locales[$lang2]['url'] . '/') . '{' . $pageSlug . ':' . $slug . '}' . $pattern;
                                $routeMeta  = [
                                    'template' => $route['template'],
                                    'pageId'   => $pageId,
                                    'isRoot'   => empty($pattern),
                                    'redirect' => false,
                                ];
                                if (!isset($routesVisited[$routeFinal][$routeMethod])) {
                                    $routes[] = ['method' => $routeMethod, 'route' => $routeFinal, 'meta' => $routeMeta];

                                    $routesVisited[$routeFinal][$routeMethod] = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $routes;
    }


    public function getIdBySlug($table, $status, $tableSlug, $redirect, $matchSlug, $langs = null) {
        if ($langs === null) $langs = $this->langs;
        $id                = null;
        $tableId           = $table . 'Id';
        $tableStatus       = $table . 'Status';
        $tableRedirect     = $table . 'Redirect';
        $tableRedirectSlug = $table . 'RedirectSlug';

        $params             = [];
        $statusGlue         = 'WHERE';
        $arraySelect        = [$table => [$tableId, $tableSlug]];
        $arraySelectWhereOr = [$table => [$tableSlug => '=']];

        $useLangs = (substr($tableSlug, -1) == '_');
        if (!$useLangs) $langs = ['nolang'];

        $otherLangs = $langs;
        $langCur    = array_shift($otherLangs);
        $otherSlugs = [];
        foreach ($otherLangs as $lang) $otherSlugs[$lang] = null;


        // setup query and param

        if ($redirect) {
            $arraySelect[$tableRedirect] = [$tableRedirectSlug];
            $otherSlugs['redirect']      = null;
        }
        if ($status != null) {
            $params[]   = ['d' => $status];
            $statusGlue = 'AND';
        }
        foreach ($langs as $lang) $params[] = ['s' => $matchSlug];
        if ($redirect) $params[] = ['s' => $matchSlug];


        // query

        $query = Sql::select($arraySelect, $langs);

        if ($redirect)
            $query .= Sql::selectLeftJoinUsing([$tableRedirect => [$tableId]]);

        if ($status != null)
            $query .= Sql::selectWhere([$table => [$tableStatus => '>=']]);

        if ($redirect)
            $arraySelectWhereOr[$tableRedirect] = [$tableRedirectSlug => '='];

        $query .= Sql::selectWhereOr($arraySelectWhereOr, $statusGlue, $langs);

        // dd($query);
        $db   = Director::getMysqli();
        $stmt = $db->prepare($query);
        $stmt->bind_param(implode(array_map('key', $params)), ...array_map('reset', $params));
        $stmt->execute();
        $result = $stmt->get_result();

        while ($data = $result->fetch_assoc()) {
            if ($data[$tableSlug . $langCur] == $matchSlug) {
                $id = $data[$tableId];
                break;
            }

            foreach ($otherLangs as $lang)
                if ($data[$tableSlug . $lang] == $matchSlug) $otherSlugs[$lang] = $data[$tableId];

            if ($redirect && $data[$tableRedirectSlug] == $matchSlug) $otherSlugs['redirect'] = $data[$tableId];
        }
        $stmt->close();


        if ($id == null) {
            foreach ($otherSlugs as $slugProjectId) {
                if ($slugProjectId != null) {
                    $id = $slugProjectId;
                    break;
                }
            }
        }

        return $id;
    }




    // sitemap

    function generateSitemap($db) {
        $pages = [];
        $query = Sql::select(['page' => ['pageSlug_', 'pageType', 'timestampModified']], $this->langs);
        $query .= 'WHERE pageStatus > 1' . PHP_EOL;
        $stmt  = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($data = $result->fetch_assoc()) {
            if (!isset($this->routes[$data['pageType']])) continue;
            $pages[$data['pageType']][] = $data;
        }
        $stmt->close();

        if (empty($pages)) {
            Flash::devlog('Sitemap not generated.');

            return;
        }

        $r = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $r .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . PHP_EOL;

        $found = 0;
        foreach ($this->routes as $pageType => $patterns) {
            foreach ($patterns as $pattern => $methods) {
                foreach ($pages[$pageType] ?? [] as $page) {
                    foreach ($methods as $method => $route) {
                        if ($method != 'GET') continue;
                        if (empty($route['sitemap'])) continue;
                        $sm = $route['sitemap'];
                        ArrayShape::languify($sm, $this->langs);
                        if (!isset($sm['priority'])) continue;

                        if (isset($sm['gcSelect'])) {
                            $statusFound = false;
                            foreach ($sm['gcSelect'][key($sm['gcSelect'])] as $fieldName) {
                                if (is_string($fieldName) && substr($fieldName, -6) == 'Status') $statusFound = $fieldName;
                            }
                            $query = Sql::select($sm['gcSelect'], $this->langs);
                            $query .= Sql::selectLeftJoinUsing($sm['gcSelectLJoin'], $this->langs);
                            if ($statusFound) $query .= 'WHERE ' . $statusFound . ' > 1' . PHP_EOL;
                            if (isset($sm['gcSelectWhere'])) {
                                if ($statusFound) $query .= 'AND' . PHP_EOL;
                                $query .= Sql::selectWhereRaw($sm['gcSelectWhere']);
                            }
                            $query .= Sql::selectGroupBy($sm['gcSelectGroupBy'], $this->langs);

                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            while ($data = $result->fetch_assoc()) {

                                $subs = [];
                                foreach ($route['sitemap']['loc'] as $col) {
                                    if (substr($col, -5) == 'MONTH' || substr($col, -3) == 'DAY') {
                                        $subs[$col] = str_pad($data[$col], 2, '0', STR_PAD_LEFT);
                                    } else if (substr($col, -1) == '_') {
                                        foreach ($this->locales as $lang => $locale) {
                                            $subs[$col][$lang] = $data[$col . $lang];
                                        }
                                    } else {
                                        $subs[$col] = $data[$col];
                                    }
                                }

                                $subLang = [];
                                foreach ($this->locales as $lang => $locale) {
                                    $subLang[$lang] = '';
                                    foreach ($subs as $col => $data) {
                                        $subLang[$lang] .= '/' . Text::hg($subs, $col, $lang);
                                    }
                                }

                                $r .= '<url>' . PHP_EOL;
                                $r .= '  <priority>' . $sm['priority'] . '</priority>' . PHP_EOL;
                                $r .= '  <loc>' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $this->addLangPrefix($page['pageSlug_' . key($this->locales)] . $subLang[key($this->locales)], key($this->locales)) . '</loc>' . PHP_EOL;
                                if (count($this->locales) > 1) {
                                    foreach ($this->locales as $lang => $locale) {
                                        $r .= '  <xhtml:link hreflang="' . $lang . '" href="' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $this->addLangPrefix($page['pageSlug_' . $lang] . $subLang[$lang], $lang) . '" rel="alternate"/>' . PHP_EOL;
                                    }
                                }
                                $r .= '</url>' . PHP_EOL;
                                $found++;
                            }
                            $stmt->close();
                        }

                        if ($pattern == '') {
                            $r .= '<url>' . PHP_EOL;
                            $r .= '  <priority>' . $sm['priority'] . '</priority>' . PHP_EOL;
                            $r .= '  <loc>' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $this->addLangPrefix($page['pageSlug_' . key($this->locales)], key($this->locales)) . '</loc>' . PHP_EOL;
                            if (count($this->locales) > 1) {
                                foreach ($this->locales as $lang => $locale) {
                                    $r .= '  <xhtml:link hreflang="' . $lang . '" href="' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $this->addLangPrefix($page['pageSlug_' . $lang], $lang) . '" rel="alternate"/>' . PHP_EOL;
                                }
                            }
                            $r .= '</url>' . PHP_EOL;
                            $found++;
                        }

                    }
                }
            }
        }

        $r .= '</urlset>' . PHP_EOL;

        if ($found > 0) {
            $result = file_put_contents($this->dir . 'public/sitemap.xml', $r);
            if ($result === false) {
                Flash::devlog('Sitemap could not be written to file.');

                return;
            }
            if ($result == 0) {
                Flash::devlog('Sitemap written with 0 bytes.');

                return;
            }

            Flash::devlog(sprintf('Sitemap generated: %d items', $found) . ' <a target="blank" href="/sitemap.xml">' . Text::t('Open in new tab') . '</a>');
        } else {
            Flash::devlog('Sitemap not generated, no items found.');
        }
    }




    // images

    public function imageGet($imgSlug, $img = [], $resize = true): array {
        $img = array_merge(AppImage::PROTO_IMAGE, $img);

        $img['name'] = $this->urlImages . $imgSlug . '/' . $imgSlug;

        if (!$img['ext'] = AppImage::valid($this->dirImage, $imgSlug)) return [];
        $imgDir     = $this->dirImage . $imgSlug . '/';
        $imgDirSlug = $imgDir . $imgSlug;


        // modified time
        $img['mtime'] = filemtime($imgDir);
        if ($img['version'] == 'mtime') $img['version'] = $img['mtime'];


        // file size
        if ($img['fileSize']) $img['fileSize'] = filesize($imgDirSlug . $img['ext']);


        // alt
        foreach ($this->langs as $lang) {
            $file = $imgDirSlug . '_alt_' . $lang . '.txt';
            if (!file_exists($file)) continue;
            $img['alt'][$lang] = file_get_contents($file);
            if (!$img['lang']) $img['lang'] = $lang;
        }


        // extra info from filesystem (type, caption_, etc)
        $img['extra'] = array_flip($img['extra']);
        foreach ($img['extra'] as $extra => $i) {
            $found = false;
            if (substr($extra, -1) == '_') {
                foreach ($this->langs as $lang) {
                    $file = $imgDirSlug . '_' . $extra . $lang . '.txt';
                    if (!file_exists($file)) continue;
                    $img['extra'][$extra][$lang] = file_get_contents($file);
                    $found                       = true;
                    break;
                }
            } else {
                $file = $imgDirSlug . '_' . $extra . '.txt';
                if (file_exists($file)) {
                    $img['extra'][$extra] = file_get_contents($file);
                    $found                = true;
                }
            }
            if (!$found) unset($img['extra'][$extra]);
        }


        // dimensions
        $file = $imgDirSlug . '_dim.txt';
        if (!file_exists($file)) return [];
        $dim              = explode('x', file_get_contents($file));
        $img['wOriginal'] = (int)$dim[0];
        $img['hOriginal'] = (int)$dim[1];

        $img = array_merge($img, AppImage::fit($img));

        if ($img['w'] == $img['wOriginal'] && $img['h'] == $img['hOriginal']) {

            $img['src'] = $img['name'] . $img['ext'];

        } else {

            $file = $imgDirSlug . '_' . $img['w'] . '_' . $img['h'] . $img['ext'];
            if ($resize && !file_exists($file)) {
                File::lock(
                    $this->dirCache . 'flock',
                    '_img_' . $imgSlug . '_' . $img['w'] . '_' . $img['h'] . $img['ext'] . '.lock',
                    function() use ($imgDir, $imgSlug, $img) {
                        try {
                            ImageVips::crop($imgDir, $imgSlug, $img['ext'], $img['w'], $img['h'], false, $img['debug']);
                        } catch (Exception $e) {
                            geD($e->getMessage(), $e->getTraceAsString());
                        }
                        touch($imgDir, $img['mtime']);
                    }
                );
            }

            $img['src'] = $img['name'] . '_' . $img['w'] . '_' . $img['h'] . $img['ext'];
        }


        foreach ($img['set'] as $setDescriptor => $set) {
            $imgResize = $img;

            $imgResize['w'] = $set['w'] ?? 0;
            $imgResize['h'] = $set['h'] ?? 0;

            $imgResize = AppImage::fit($imgResize);
            if ($imgResize['w'] > $img['wOriginal'] || !$set['w'] || !$set['h']) {
                unset($img['set'][$setDescriptor]);
                continue;
            }

            if (is_int($setDescriptor)) $setDescriptor = $imgResize['w'] . 'w';

            if ($imgResize['w'] == $img['wOriginal'] && $imgResize['h'] == $img['hOriginal']) {
                $img['srcset'] .= $img['name'] . $img['ext'] . ' ' . $setDescriptor . ', ';
            } else {

                $file = $imgDirSlug . '_' . $imgResize['w'] . '_' . $imgResize['h'] . $img['ext'];

                if ($resize && !file_exists($file)) {
                    File::lock(
                        $this->dirCache . 'flock',
                        '_img_' . $imgSlug . '_' . $imgResize['w'] . '_' . $imgResize['h'] . $img['ext'] . '.lock',
                        function() use ($imgDir, $imgSlug, $imgResize) {
                            try {
                                ImageVips::crop($imgDir, $imgSlug, $imgResize['ext'], $imgResize['w'], $imgResize['h'], false, $imgResize['debug']);
                            } catch (Exception $e) {
                                geD($e->getMessage(), $e->getTraceAsString());
                            }
                            touch($imgDir, $imgResize['mtime']);
                        }
                    );
                }
                $img['srcset'] .= $img['name'] . '_' . $imgResize['w'] . '_' . $imgResize['h'] . $img['ext'] . ' ' . $setDescriptor . ', ';
            }
        }

        $img['srcset'] = rtrim($img['srcset'], ', ');
        if (count($img['set']) == 0) $img['srcset'] = '';

        return $img;
    }


    public function imageUpload(array $files, $replaceDefault = false, int $toFitDefault = 0, string $type = '') {
        $uploaded = [];

        uasort($files, function($a, $b) {
            return $a['tmp_name'] <=> $b['tmp_name'];
        });

        foreach ($files as $file) {

            $fileNameTemp     = $file['tmp_name'];
            $fileNameProposed = $file['name'];

            $mtime            = false;
            $fileNameProposed = Text::normalize($fileNameProposed, ' ', '.');
            $shouldReplace    = false;

            $fileReplace = $replaceDefault;
            if (isset($file['imgExisting'])) {
                switch ($file['imgExisting']) {
                    case 'ignore':
                        Flash::warning('Ignored image: ' . Text::h($fileNameProposed));
                        continue 2;

                    case 'rename':
                        $fileReplace = false;
                        break;

                    case 'replace':
                        $fileReplace = true;
                        break;
                }
            }

            // load image
            try {
                $imageVips = new ImageVips($fileNameTemp);
            } catch (Exception $e) {
                Flash::error($e->getMessage());
                Flash::devlog($e->getTraceAsString());
                continue;
            }

            // prepare directories
            $fileSlug   = $fileSlugInitial = pathinfo($fileNameProposed, PATHINFO_FILENAME);
            $fileSlug   = Text::formatSlug($fileSlug);
            $fileDir    = $this->dirImage . $fileSlug . '/';
            $dirCreated = false;
            if (is_dir($this->dirImage . $fileSlug)) {
                if ($fileReplace) {
                    $mtime         = filemtime($this->dirImage . $fileSlug . '/');
                    $shouldReplace = true;
                } else {
                    for ($j = 0; $j < 3; $j++) {
                        if (!is_dir($this->dirImage . $fileSlug)) break;
                        $fileSlug = Text::formatSlug('temp' . uniqid() . '-' . $fileSlugInitial);
                        $fileDir  = $this->dirImage . $fileSlug . '/';
                    }
                    if (mkdir($fileDir)) {
                        $dirCreated = true;
                    } else {
                        Flash::error('Unable to create directory: ' . Text::h($fileDir));
                        continue;
                    }
                }
            } else {
                if (mkdir($fileDir)) {
                    $dirCreated = true;
                } else {
                    Flash::error('Unable to create directory: ' . Text::h($fileDir));
                    continue;
                }
            }

            try {
                $imageVips->save($fileDir . $fileSlug, $fileReplace, $file['toFit'] ?? $toFitDefault);
            } catch (Exception $e) {
                Flash::error($e->getMessage());
                Flash::devlog($e->getTraceAsString());
                if ($dirCreated) AppImage::delete($this->dirImage, $fileSlug);
                if ($dirCreated) rmdir($fileDir);
                continue;
            }


            if ($shouldReplace) {
                foreach (ImageVips::FORMATS as $format) {
                    if ($format == $imageVips->ext) continue;
                    if (file_exists($fileDir . $fileSlug . $format)) unlink($fileDir . $fileSlug . $format);
                }
                AppImage::deleteResizes($this->dirImage, $fileSlug);
            }


            // dimensions
            file_put_contents($fileDir . $fileSlug . '_dim.txt', $imageVips->w . 'x' . $imageVips->h);


            // type
            if ($file['imgType'] ?? $type) {
                file_put_contents($fileDir . $fileSlug . '_type.txt', Text::h($file['imgType'] ?? $type));
            }


            // finish
            $fileNameStripped = pathinfo($fileNameProposed, PATHINFO_FILENAME);
            if ($fileReplace) {
                if ($imageVips->resized)
                    Flash::info('Replaced and resized image: ' . Text::h($fileSlug . $imageVips->ext));
                else
                    Flash::info('Replaced image: ' . Text::h($fileSlug . $imageVips->ext));

                if ($mtime) {
                    touch($fileDir . $fileSlug . $imageVips->ext, $mtime);
                    touch($this->dirImage . $fileSlug . '/', $mtime);
                }
            } else {
                Flash::info('Uploaded image: ' . Text::h($fileSlug . $imageVips->ext));
            }
            $uploaded[] = [
                'slug'     => $fileSlug,
                'fileName' => $fileNameStripped,
                'ext'      => $imageVips->ext,
                'replaced' => $fileReplace,
                'type'     => $file['imgType'] ?? $type,
            ];
        }

        return $uploaded;
    }




    // caching

    function cacheGet(
        string $scope, int $level, string $key,
        callable $f, bool $bypass = null, bool $write = null
    ): array {

        $subdir = 'app';
        if ($scope == 'editor') $subdir = 'editor';

        $cacheName = $scope . '-' . $level . '-' . $key;

        $dir = $this->dirCache . trim($subdir, '/') . '/';
        if (!is_dir($dir)) mkdir($dir);

        if (is_null($bypass)) $bypass = ($this->cacheBypass == true);
        if (is_null($write)) $write = !$bypass;
        if (!$bypass) $write = true;

        $cacheFile = $dir . $cacheName . '.cache';

        if (!$bypass && file_exists($cacheFile)) {

            $timerName = 'Cache HIT: ' . $cacheName;
            Director::timerStart($timerName);

            $result = include $cacheFile;

        } else {

            $result    = null;
            $cacheType = $bypass ? 'BYPASS' : 'MISS';
            $timerName = 'Cache ' . $cacheType . ': ' . $cacheName;
            Director::timerStart($timerName);

            $fImageWrite = function() use ($f, $write, $cacheFile) {
                $r = $f();
                if ($write && is_array($r)) {
                    file_put_contents($cacheFile, '<?php return ' . var_export($r, true) . ';' . PHP_EOL);
                }

                return $r;
            };

            if ($bypass) {
                $result = $fImageWrite();
            } else {
                $result = File::lock($this->dirCache . 'flock', $cacheName . '.lock', $fImageWrite);
            }

        }

        if (!is_array($result)) {
            Flash::error('Cache: invalid result');
        }

        Director::timerStop($timerName);

        return $result ?? [];
    }


    function cacheDelete($scopes, $key = '*') {
        $dirCacheStrlen = strlen($this->dirCache);
        if (!is_array($scopes)) $scopes = [$scopes];
        if (in_array('editor', $scopes) && !in_array('app', $scopes)) $scopes[] = 'app';
        $files = [];
        foreach ($scopes as $scope) {
            $dir = 'app/';
            if ($scope == 'editor') $dir = 'editor/';

            $cacheName = $scope . '-*-' . $key;
            $pattern   = $this->dirCache . $dir . $cacheName . '.cache';
            $glob      = glob($pattern, GLOB_NOSORT);
            foreach ($glob as $file) {
                if (isset($files[$file])) continue;
                preg_match('~^\w+-(\d+)-~', substr($file, $dirCacheStrlen), $matches);
                $files[$file] = $matches[1] ?? '999';
            }
        }
        asort($files, SORT_NUMERIC);

        $deleted = 0;
        $total   = 0;
        foreach ($files as $fileName => $level) {
            if (unlink($fileName)) $deleted++;
            $total++;
        }

        Flash::devlog(implode(', ', $scopes) . ': caches deleted: ' . $deleted . '/' . $total);

        $pattern = $this->dirCache . 'editor/list-history-*.cache';
        $glob    = glob($pattern, GLOB_NOSORT);
        foreach ($glob as $fileName) unlink($fileName);

        if (is_dir($this->dirCache . 'nginx/')) {
            $glob    = glob($this->dirCache . 'nginx/*/*/*');
            $deleted = 0;
            $total   = 0;
            foreach ($glob as $fileName) {
                if (unlink($fileName)) $deleted++;
                $total++;
            }
            if ($deleted) Flash::devlog('nginx caches deleted: ' . $deleted . '/' . $total);
        }
        if (is_dir($this->dirCache . 'nginxAjax/')) {
            $glob    = glob($this->dirCache . 'nginxAjax/*/*/*');
            $deleted = 0;
            $total   = 0;
            foreach ($glob as $fileName) {
                if (unlink($fileName)) $deleted++;
                $total++;
            }
            if ($deleted) Flash::devlog('nginxAjax caches deleted: ' . $deleted . '/' . $total);
        }
    }


    function cacheDeleteAll() {
        $dirCacheStrlen = strlen($this->dirCache);
        $files          = [];

        $glob = glob($this->dirCache . 'app/*.cache', GLOB_NOSORT);
        foreach ($glob as $file) {
            preg_match('~^\w+-(\d+)-~', substr($file, $dirCacheStrlen), $matches);
            $files[$file] = $matches[1] ?? '999';
        }
        $glob = glob($this->dirCache . 'editor/*.cache', GLOB_NOSORT);
        foreach ($glob as $file) {
            preg_match('~^\w+-(\d+)-~', substr($file, $dirCacheStrlen), $matches);
            $files[$file] = $matches[1] ?? '999';
        }

        asort($files, SORT_NUMERIC);

        $deleted = 0;
        $total   = 0;
        foreach ($files as $fileName => $level) {
            if (unlink($fileName)) $deleted++;
            $total++;
        }

        Flash::devlog('ALL caches deleted: ' . $deleted . '/' . $total);

        if (is_dir($this->dirCache . 'nginx/')) {
            $glob    = glob($this->dirCache . 'nginx/*/*/*');
            $deleted = 0;
            $total   = 0;
            foreach ($glob as $fileName) {
                if (unlink($fileName)) $deleted++;
                $total++;
            }
            if ($deleted) Flash::devlog('nginx caches deleted: ' . $deleted);
        }
        if (is_dir($this->dirCache . 'nginxAjax/')) {
            $glob    = glob($this->dirCache . 'nginxAjax/*/*/*');
            $deleted = 0;
            $total   = 0;
            foreach ($glob as $fileName) {
                if (unlink($fileName)) $deleted++;
                $total++;
            }
            if ($deleted) Flash::devlog('nginxAjax caches deleted: ' . $deleted);
        }
    }


    function cacheDeleteOld() {
        $pattern = $this->dirCache . '*.cache';
        $glob    = glob($pattern, GLOB_NOSORT);

        $now     = time();
        $old     = 60 * 60 * 24 * 3; // 3 days
        $deleted = 0;

        foreach ($glob as $fileName)
            if (is_file($fileName))
                if ($now - filemtime($fileName) >= $old)
                    if (unlink($fileName)) $deleted++;

        Flash::devlog('App old caches deleted: ' . $deleted);
    }

}
