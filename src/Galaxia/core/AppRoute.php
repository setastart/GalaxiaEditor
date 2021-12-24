<?php


namespace Galaxia;

use Galaxia\FastRoute\Dispatcher;
use function Galaxia\FastRoute\cachedDispatcher;


class AppRoute {

    static function generateSitemap(string $schemeHost) {
        $activeLocales = array_diff_key(G::$app->locales, G::$app->localesInactive);
        $activeLangs   = array_keys($activeLocales);
        $keyLang       = key($activeLocales);

        $pages = [];
        $query = Sql::select(['page' => ['pageSlug_', 'pageType', 'timestampModified']], $activeLangs);
        $query .= 'WHERE pageStatus > 1' . PHP_EOL;
        $stmt  = G::prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($data = $result->fetch_assoc()) {
            if (!isset(G::$app->routes[$data['pageType']])) continue;
            $pages[$data['pageType']][] = $data;
        }
        $stmt->close();

        if (empty($pages)) {
            Flash::devlog('Sitemap not generated.');

            return;
        }

        $urls  = [];
        $found = 0;
        foreach (G::$app->routes as $pageType => $patterns) {
            foreach ($patterns as $pattern => $methods) {
                foreach ($pages[$pageType] ?? [] as $page) {
                    foreach ($methods as $method => $route) {
                        if ($method != 'GET') continue;
                        if (empty($route['sitemap'])) continue;
                        $sm = $route['sitemap'];
                        ArrayShape::languify($sm, $activeLangs);
                        if (!isset($sm['priority'])) continue;

                        if (isset($sm['gcSelect'])) {
                            $statusFound = false;
                            foreach ($sm['gcSelect'][key($sm['gcSelect'])] as $fieldName) {
                                if (is_string($fieldName) && substr($fieldName, -6) == 'Status') $statusFound = $fieldName;
                            }
                            $query = Sql::select($sm['gcSelect'], $activeLangs);
                            $query .= Sql::selectLeftJoinUsing($sm['gcSelectLJoin'], $activeLangs);
                            if ($statusFound) $query .= 'WHERE ' . $statusFound . ' > 1' . PHP_EOL;
                            if (isset($sm['gcSelectWhere'])) {
                                if ($statusFound) $query .= 'AND' . PHP_EOL;
                                $query .= Sql::selectWhereRaw($sm['gcSelectWhere']);
                            }
                            $query .= Sql::selectGroupBy($sm['gcSelectGroupBy'], $activeLangs);

                            $stmt = G::prepare($query);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            while ($data = $result->fetch_assoc()) {

                                $subs = [];
                                foreach ($route['sitemap']['loc'] as $col) {
                                    if (substr($col, -5) == 'MONTH' || substr($col, -3) == 'DAY') {
                                        $subs[$col] = str_pad($data[$col], 2, '0', STR_PAD_LEFT);
                                    } else if (substr($col, -1) == '_') {
                                        foreach ($activeLocales as $lang => $locale) {
                                            $subs[$col][$lang] = $data[$col . $lang];
                                        }
                                    } else {
                                        $subs[$col] = $data[$col];
                                    }
                                }

                                $subLang = [];
                                foreach ($activeLocales as $lang => $locale) {
                                    $subLang[$lang] = '';
                                    foreach ($subs as $col => $data) {
                                        $subLang[$lang] .= '/' . Text::hg($subs, $col, $lang);
                                    }
                                }

                                $urls[$found] = [
                                    $keyLang => G::addLangPrefix($page['pageSlug_' . $keyLang] . $subLang[$keyLang], $keyLang),

                                    'pri' => $sm['priority'],
                                ];

                                if (count($activeLocales) > 1) {
                                    foreach ($activeLocales as $lang => $locale) {
                                        $urls[$found][$lang] = G::addLangPrefix($page['pageSlug_' . $lang] . $subLang[$lang], $lang);
                                    }
                                }
                                $found++;
                            }
                            $stmt->close();
                        }

                        if ($pattern == '') {
                            $urls[$found] = [
                                $keyLang => G::addLangPrefix($page['pageSlug_' . $keyLang], $keyLang),

                                'pri' => $sm['priority'],
                            ];

                            if (count($activeLocales) > 1) {
                                foreach ($activeLocales as $lang => $locale) {
                                    $urls[$found][$lang] = G::addLangPrefix($page['pageSlug_' . $lang], $lang);
                                }
                            }
                            $found++;
                        }

                    }
                }
            }
        }


        if ($found > 0) {
            foreach ($activeLocales as $lang => $locale) {
                $fileName = 'sitemap_' . $lang . '.xml';
                if ($lang == $keyLang) $fileName = 'sitemap.xml';


                $rl = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
                $rl .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . PHP_EOL;

                foreach ($urls as $url) {
                    $rl .= '<url>' . PHP_EOL;
                    $rl .= '  <priority>' . $url['pri'] . '</priority>' . PHP_EOL;
                    $rl .= '  <loc>' . $schemeHost . $url[$lang] . '</loc>' . PHP_EOL;
                    if (count($activeLocales) > 1) {
                        foreach ($activeLocales as $lang2 => $locale) {
                            $rl .= '  <xhtml:link hreflang="' . $lang2 . '" href="' . $schemeHost . $url[$lang2] . '" rel="alternate"/>' . PHP_EOL;
                        }
                    }
                    $rl .= '</url>' . PHP_EOL;
                }
                $rl .= '</urlset>' . PHP_EOL;

                $result = file_put_contents(G::$app->dir . 'public/' . $fileName, $rl);
                if ($result === false) {
                    Flash::devlog('Sitemap could not be written to file.');

                    return;
                }
                if ($result == 0) {
                    Flash::devlog(sprintf('Sitemap written with 0 bytes - %s.', $fileName));

                    return;
                }

                Flash::devlog(sprintf('Sitemap generated: %d items - %s', $found, $fileName) . ' <a target="blank" href="/' . $fileName . '">' . Text::t('Open in new tab') . '</a>');
            }
        } else {
            Flash::devlog('Sitemap not generated, no items found.');
        }
    }




    static function list(int $pageMinStatus, $pageSlug = 'pgSlug'): array {
        $routes                  = [];
        $routesVisited           = [];
        $slugsAndRedirectsByType = [];


        $query = Sql::select([
            'page'         => ['pageId', 'pageStatus', 'pageSlug_', 'pageType'],
            'pageRedirect' => ['pageRedirectId', 'pageRedirectSlug'],
        ], G::$app->langs);

        $query .= Sql::selectLeftJoinUsing(['pageRedirect' => ['pageId']]);

        $query .= 'WHERE pageStatus >= ' . $pageMinStatus . PHP_EOL;

        $stmt = G::prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($data = $result->fetch_assoc()) {
            if (!isset(G::$app->routes[$data['pageType']])) continue;

            foreach (G::$app->langs as $lang)
                $slugsAndRedirectsByType['slugs'][$data['pageType']][$data['pageId']][$lang] = $data['pageSlug_' . $lang];

            if ($data['pageRedirectSlug'])
                $slugsAndRedirectsByType['redirects'][$data['pageType']][$data['pageId']][$data['pageRedirectId']] = $data['pageRedirectSlug'];
        }
        $stmt->close();


        // main lang
        foreach (G::$app->routes as $pageType => $patterns) {
            if (!isset($slugsAndRedirectsByType['slugs'][$pageType])) continue;
            foreach ($patterns as $pattern => $methods) {
                foreach ($slugsAndRedirectsByType['slugs'][$pageType] as $pageId => $page) {
                    foreach ($methods as $routeMethod => $route) {
                        foreach ($page as $lang => $slug) {
                            if ($slug == '') $routeFinal = G::$app->locales[$lang]['url'] . '{' . $pageSlug . ':' . $slug . '}' . $pattern;
                            else $routeFinal = ((G::$app->locales[$lang]['url'] == '/') ? '/' : G::$app->locales[$lang]['url'] . '/') . '{' . $pageSlug . ':' . $slug . '}' . $pattern;
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
        foreach (G::$app->routes as $pageType => $patterns) {
            if (!isset($slugsAndRedirectsByType['redirects'][$pageType])) continue;
            foreach ($patterns as $pattern => $methods) {
                foreach ($slugsAndRedirectsByType['redirects'][$pageType] as $pageId => $redirect) {
                    foreach ($methods as $routeMethod => $route) {
                        if ($routeMethod != 'GET') continue;
                        foreach ($redirect as $redirectId => $slug) {
                            if (!$slug) continue;
                            foreach (G::$app->langs as $lang) {
                                $routeFinal = ((G::$app->locales[$lang]['url'] == '/') ? '/' : G::$app->locales[$lang]['url'] . '/') . '{' . $pageSlug . ':' . $slug . '}' . $pattern;
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
        foreach (G::$app->routes as $pageType => $patterns) {
            if (!isset($slugsAndRedirectsByType['slugs'][$pageType])) continue;
            foreach ($patterns as $pattern => $methods) {
                foreach ($slugsAndRedirectsByType['slugs'][$pageType] as $pageId => $page) {
                    foreach ($methods as $routeMethod => $route) {
                        if ($routeMethod != 'GET') continue;
                        foreach ($page as $lang => $slug) {
                            foreach (G::$app->langs as $lang2) {
                                if ($lang2 == $lang) continue;
                                if (!$slug) continue;
                                $routeFinal = ((G::$app->locales[$lang2]['url'] == '/') ? '/' : G::$app->locales[$lang2]['url'] . '/') . '{' . $pageSlug . ':' . $slug . '}' . $pattern;
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




    static function slugToId($table, $status, $tableSlug, $redirect, $matchSlug, $langs): ?int {
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
        $stmt = G::prepare($query);
        $stmt->bind_param(implode(array_map('key', $params)), ...array_map(fn($a) => $a[key($a)], $params));
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




    static function page(callable $f, string $cacheFile, bool $cacheDisabled) {
        G::timerStart(__CLASS__ . '::' . __FUNCTION__);

        $dispatcher = cachedDispatcher($f, ['cacheFile' => $cacheFile, 'cacheDisabled' => $cacheDisabled]);
        $routeInfo = $dispatcher->dispatch(G::$req->method, G::$req->path);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                G::errorPage(404, 'Route not found.');
                break;

            case Dispatcher::METHOD_NOT_ALLOWED:
                G::errorPage(403, 'Method not allowed.');
                break;

            case Dispatcher::FOUND:
                G::$req->pagId      = $routeInfo[1]['pageId'] ?? 0;
                G::$req->isRoot     = $routeInfo[1]['isRoot'] ?? false;
                G::$req->route      = $routeInfo[1]['template'] ?? '';
                G::$req->redirectId = $routeInfo[1]['redirect'] ?? 0;
                G::$req->vars       = $routeInfo[2];
                break;
        }

        G::timerStop(__CLASS__ . '::' . __FUNCTION__);
    }

}
