<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


use DirectoryIterator;
use Exception;


class AppImage {

    const name      = 'name';
    const time      = 'time';
    const slug      = 'slug';
    const ext       = 'ext';
    const webp      = 'webp';
    const mtime     = 'mtime';
    const fileSize  = 'fileSize';
    const w         = 'w';
    const h         = 'h';
    const wOriginal = 'wOriginal';
    const hOriginal = 'hOriginal';
    const fit       = 'fit';
    const version   = 'version';
    const id        = 'id';
    const cls       = 'class';
    const src       = 'src';
    const set       = 'set';
    const srcset    = 'srcset';
    const sizes     = 'sizes';
    const alt       = 'alt';
    const lang      = 'lang';
    const extra     = 'extra';
    const loading   = 'loading';
    const debug     = 'debug';

    public const proto = [
        self::name      => '',
        self::slug      => '',
        self::ext       => '',
        self::webp      => false,
        self::mtime     => '',
        self::fileSize  => 0,
        self::w         => 0,
        self::h         => 0,
        self::wOriginal => 0,
        self::hOriginal => 0,
        self::fit       => 'contain',
        self::version   => '',
        self::id        => '',
        self::cls       => '',
        self::src       => '',
        self::set       => [],
        self::srcset    => '',
        self::sizes     => '',
        self::alt       => [],
        self::lang      => '',
        self::extra     => [],
        self::loading   => 'lazy',
        self::debug     => false,
    ];




    static function list($dirImage): array {
        $images = [];
        $glob   = glob($dirImage . '*', GLOB_NOSORT);
        if ($glob === false) return $images;
        foreach ($glob as $filename) {
            if (!is_dir($filename)) continue;
            $basename          = basename($filename);
            $images[$basename] = [
                self::name => $basename,
                self::time => filemtime($filename),
            ];
        }

        uasort($images, function($a, $b) {
            if ($a[self::time] == $b[self::time]) return strnatcmp($a[self::name], $b[self::name]);

            return $b[self::time] <=> $a[self::time];
        });

        foreach ($images as $key => $image) {
            $images[$key] = $image[self::time];
        }

        return $images;
    }




    static function valid(string $dirImage, string $imgSlug): bool|string {
        if (empty($imgSlug)) return false;
        if (preg_match('/[^a-z0-9-]/', $imgSlug)) return false;
        if (realpath($dirImage) == realpath($dirImage . $imgSlug)) return false;
        if (!is_dir($dirImage . $imgSlug)) return false;

        $fileBase = $dirImage . $imgSlug . '/' . $imgSlug;
        if (file_exists($fileBase . '.jpg')) return '.jpg';
        if (file_exists($fileBase . '.png')) return '.png';

        return false;
    }




    // images

    static function imageGet($imgSlug, $img = [], $resize = true): array {
        $img = array_merge(self::proto, $img);

        $img[self::name] = G::$app->urlImages . $imgSlug . '/' . $imgSlug;
        $img[self::slug] = $imgSlug;

        if (!$img[self::ext] = self::valid(G::$app->dirImage, $imgSlug)) return [];
        $imgDir     = G::$app->dirImage . $imgSlug . '/';
        $imgDirSlug = $imgDir . $imgSlug;


        // modified time
        $img[self::mtime] = filemtime($imgDir);
        if ($img[self::version] == self::mtime) $img[self::version] = $img[self::mtime];


        // file size
        if ($img[self::fileSize]) $img[self::fileSize] = filesize($imgDirSlug . $img[self::ext]);


        // alt
        foreach (G::$app->langs as $lang) {
            $file = $imgDirSlug . '_alt_' . $lang . '.txt';
            if (!file_exists($file)) continue;
            $img[self::alt][$lang] = file_get_contents($file);
            if (!$img[self::lang]) $img[self::lang] = $lang;
        }


        // extra info from filesystem (type, caption_, etc)
        $img[self::extra] = array_flip($img[self::extra]);
        foreach ($img[self::extra] as $extra => $i) {
            $found = false;
            if (str_ends_with($extra, '_')) {
                foreach (G::$app->langs as $lang) {
                    $file = $imgDirSlug . '_' . $extra . $lang . '.txt';
                    if (!file_exists($file)) continue;
                    $img[self::extra][$extra][$lang] = file_get_contents($file);
                    $found                           = true;
                    break;
                }
            } else {
                $file = $imgDirSlug . '_' . $extra . '.txt';
                if (file_exists($file)) {
                    $img[self::extra][$extra] = file_get_contents($file);
                    $found                    = true;
                }
            }
            if (!$found) unset($img[self::extra][$extra]);
        }


        // dimensions
        $file = $imgDirSlug . '_dim.txt';
        if (!file_exists($file)) return [];
        $dim                  = explode('x', file_get_contents($file));
        $img[self::wOriginal] = (int)$dim[0];
        $img[self::hOriginal] = (int)$dim[1];

        $img = array_merge($img, self::fit($img));

        if ($img[self::w] == $img[self::wOriginal] && $img[self::h] == $img[self::hOriginal]) {

            $file = $imgDirSlug . '_' . $img[self::w] . '_' . $img[self::h] . '.webp';
            if ($img[self::webp] && !file_exists($file)) {
                File::lock(
                    G::$app->dirCache . 'flock',
                    '_img_' . $imgSlug . '_' . $img[self::w] . '_' . $img[self::h] . '.webp' . '.lock',
                    function() use ($imgDir, $imgSlug, $img) {
                        try {
                            ImageVips::crop($imgDir, $imgSlug, $img[self::ext], $img[self::w], $img[self::h], false, $img[self::debug], true);
                        } catch (Exception $e) {
                            d($e->getMessage(), $e->getTraceAsString());
                        }
                        touch($imgDir, $img[self::mtime]);
                    }
                );
            }

            $img[self::src] = $img[self::name] . $img[self::ext];

        } else {

            $file = $imgDirSlug . '_' . $img[self::w] . '_' . $img[self::h] . $img[self::ext];
            if ($resize && !file_exists($file)) {
                File::lock(
                    G::$app->dirCache . 'flock',
                    '_img_' . $imgSlug . '_' . $img[self::w] . '_' . $img[self::h] . $img[self::ext] . '.lock',
                    function() use ($imgDir, $imgSlug, $img) {
                        try {
                            ImageVips::crop($imgDir, $imgSlug, $img[self::ext], $img[self::w], $img[self::h], false, $img[self::debug]);
                        } catch (Exception $e) {
                            d($e->getMessage(), $e->getTraceAsString());
                        }
                        touch($imgDir, $img[self::mtime]);
                    }
                );
            }

            $file = $imgDirSlug . '_' . $img[self::w] . '_' . $img[self::h] . '.webp';
            if ($img[self::webp] && $resize && !file_exists($file)) {
                File::lock(
                    G::$app->dirCache . 'flock',
                    '_img_' . $imgSlug . '_' . $img[self::w] . '_' . $img[self::h] . '.webp' . '.lock',
                    function() use ($imgDir, $imgSlug, $img) {
                        try {
                            ImageVips::crop($imgDir, $imgSlug, $img[self::ext], $img[self::w], $img[self::h], false, $img[self::debug], true);
                        } catch (Exception $e) {
                            d($e->getMessage(), $e->getTraceAsString());
                        }
                        touch($imgDir, $img[self::mtime]);
                    }
                );
            }

            $img[self::src] = $img[self::name] . '_' . $img[self::w] . '_' . $img[self::h] . $img[self::ext];
        }


        foreach ($img[self::set] as $setDescriptor => $set) {
            $imgResize = $img;

            $imgResize[self::w] = $set[self::w] ?? 0;
            $imgResize[self::h] = $set[self::h] ?? 0;

            $imgResize = self::fit($imgResize);
            if ($imgResize[self::w] > $img[self::wOriginal] || !$set[self::w] || !$set[self::h]) {
                unset($img[self::set][$setDescriptor]);
                continue;
            }

            if (is_int($setDescriptor)) $setDescriptor = $imgResize[self::w] . self::w;

            if ($imgResize[self::w] == $img[self::wOriginal] && $imgResize[self::h] == $img[self::hOriginal]) {

                $file = $imgDirSlug . '_' . $imgResize[self::w] . '_' . $imgResize[self::h] . '.webp';
                if ($img[self::webp] && $resize && !file_exists($file)) {
                    File::lock(
                        G::$app->dirCache . 'flock',
                        '_img_' . $imgSlug . '_' . $imgResize[self::w] . '_' . $imgResize[self::h] . '.webp' . '.lock',
                        function() use ($imgDir, $imgSlug, $imgResize, $img) {
                            try {
                                if ($img[self::webp]) ImageVips::crop($imgDir, $imgSlug, $imgResize[self::ext], $imgResize[self::w], $imgResize[self::h], false, $imgResize[self::debug], true);
                            } catch (Exception $e) {
                                d($e->getMessage(), $e->getTraceAsString());
                            }
                            touch($imgDir, $imgResize[self::mtime]);
                        }
                    );
                }

                $img[self::srcset] .= $img[self::name] . $img[self::ext] . ' ' . $setDescriptor . ', ';
            } else {

                $file = $imgDirSlug . '_' . $imgResize[self::w] . '_' . $imgResize[self::h] . $img[self::ext];
                if ($resize && !file_exists($file)) {
                    File::lock(
                        G::$app->dirCache . 'flock',
                        '_img_' . $imgSlug . '_' . $imgResize[self::w] . '_' . $imgResize[self::h] . $img[self::ext] . '.lock',
                        function() use ($imgDir, $imgSlug, $imgResize, $img) {
                            try {
                                ImageVips::crop($imgDir, $imgSlug, $imgResize[self::ext], $imgResize[self::w], $imgResize[self::h], false, $imgResize[self::debug]);
                            } catch (Exception $e) {
                                d($e->getMessage(), $e->getTraceAsString());
                            }
                            touch($imgDir, $imgResize[self::mtime]);
                        }
                    );
                }

                $file = $imgDirSlug . '_' . $imgResize[self::w] . '_' . $imgResize[self::h] . '.webp';
                if ($img[self::webp] && $resize && !file_exists($file)) {
                    File::lock(
                        G::$app->dirCache . 'flock',
                        '_img_' . $imgSlug . '_' . $imgResize[self::w] . '_' . $imgResize[self::h] . '.webp' . '.lock',
                        function() use ($imgDir, $imgSlug, $imgResize, $img) {
                            try {
                                ImageVips::crop($imgDir, $imgSlug, $imgResize[self::ext], $imgResize[self::w], $imgResize[self::h], false, $imgResize[self::debug], true);
                            } catch (Exception $e) {
                                d($e->getMessage(), $e->getTraceAsString());
                            }
                            touch($imgDir, $imgResize[self::mtime]);
                        }
                    );
                }
                $img[self::srcset] .= $img[self::name] . '_' . $imgResize[self::w] . '_' . $imgResize[self::h] . $img[self::ext] . ' ' . $setDescriptor . ', ';
            }
        }

        $img[self::srcset] = rtrim($img[self::srcset], ', ');
        if (count($img[self::set]) == 0) $img[self::srcset] = '';

        return $img;
    }


    static function imageUpload(array $files, $replaceDefault = false, int $toFitDefault = 0, string $type = ''): array {
        $uploaded = [];

        uasort($files, function($a, $b) {
            return $a['tmp_name'] <=> $b['tmp_name'];
        });

        foreach ($files as $file) {

            $fileNameTemp     = $file['tmp_name'];
            $fileNameProposed = $file[self::name];

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
            $fileDir    = G::$app->dirImage . $fileSlug . '/';
            $dirCreated = false;
            if (is_dir(G::$app->dirImage . $fileSlug)) {
                if ($fileReplace) {
                    $mtime         = filemtime(G::$app->dirImage . $fileSlug . '/');
                    $shouldReplace = true;
                } else {
                    for ($j = 0; $j < 3; $j++) {
                        if (!is_dir(G::$app->dirImage . $fileSlug)) break;
                        $fileSlug = Text::formatSlug('temp' . uniqid() . '-' . $fileSlugInitial);
                        $fileDir  = G::$app->dirImage . $fileSlug . '/';
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
                if ($dirCreated) self::delete(G::$app->dirImage, $fileSlug);
                if ($dirCreated) rmdir($fileDir);
                continue;
            }


            if ($shouldReplace) {
                foreach (ImageVips::FORMATS as $format) {
                    if ($format == $imageVips->ext) continue;
                    if (file_exists($fileDir . $fileSlug . $format)) unlink($fileDir . $fileSlug . $format);
                }
                self::deleteResizes(G::$app->dirImage, $fileSlug);
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
                if ($imageVips->resized ?? false)
                    Flash::info('Replaced and resized image: ' . Text::h($fileSlug . $imageVips->ext));
                else
                    Flash::info('Replaced image: ' . Text::h($fileSlug . $imageVips->ext));

                if ($mtime) {
                    touch($fileDir . $fileSlug . $imageVips->ext, $mtime);
                    touch(G::$app->dirImage . $fileSlug . '/', $mtime);
                }
            } else {
                Flash::info('Uploaded image: ' . Text::h($fileSlug . $imageVips->ext));
            }
            $uploaded[] = [
                self::slug => $fileSlug,
                'fileName' => $fileNameStripped,
                self::ext  => $imageVips->ext,
                'replaced' => $fileReplace,
                'type'     => $file['imgType'] ?? $type,
            ];
        }

        return $uploaded;
    }




    static function dimensions(string $dirImage, string $imgSlug): array {
        $file = $dirImage . $imgSlug . '/' . $imgSlug . '_dim.txt';
        if (!file_exists($file)) return [];
        $dim = explode('x', file_get_contents($file));

        return ($dim) ? [(int)$dim[0], (int)$dim[1]] : [];
    }




    static function fit($img): array {
        $img[self::wOriginal] = (int)$img[self::wOriginal];
        $img[self::hOriginal] = (int)$img[self::hOriginal];

        if ($img[self::w] == 0 && $img[self::h] == 0) {
            $img[self::w] = $img[self::wOriginal];
            $img[self::h] = $img[self::hOriginal];

            return $img;
        }

        $ratioOriginal = $img[self::wOriginal] / $img[self::hOriginal];

        if ($img[self::w] == 0) {
            if ($img[self::h] > $img[self::hOriginal]) $img[self::h] = $img[self::hOriginal];
            $img[self::w] = (int)round($img[self::h] * $ratioOriginal);

            return $img;
        }

        if ($img[self::h] == 0) {
            if ($img[self::w] > $img[self::wOriginal]) $img[self::w] = $img[self::wOriginal];
            $img[self::h] = (int)round($img[self::w] / $ratioOriginal);

            return $img;
        }

        if (!in_array($img[self::fit] ?? '', ['contain', 'cover', 'expand'])) $img[self::fit] = 'contain';

        $ratioFit = $img[self::w] / $img[self::h];

        if ($img[self::fit] == 'contain') {
            if ($ratioFit > $ratioOriginal) {
                if ($img[self::h] > $img[self::hOriginal]) $img[self::h] = $img[self::hOriginal];
                $img[self::w] = (int)round($img[self::h] * $ratioOriginal);
            } else {
                if ($img[self::w] > $img[self::wOriginal]) $img[self::w] = $img[self::wOriginal];
                $img[self::h] = (int)round($img[self::w] / $ratioOriginal);
            }
        } else if ($img[self::fit] == 'cover') {
            if ($img[self::w] > $img[self::wOriginal]) {
                $img[self::w] = $img[self::wOriginal];
                $img[self::h] = (int)round($img[self::w] / $ratioFit);
            }

            if ($img[self::h] > $img[self::hOriginal]) {
                $img[self::h] = $img[self::hOriginal];
                $img[self::w] = (int)round($img[self::h] * $ratioFit);
            }
        } else {
            if ($ratioFit > $ratioOriginal) {
                if ($img[self::w] > $img[self::wOriginal]) $img[self::w] = $img[self::wOriginal];
                $img[self::h] = (int)round($img[self::w] / $ratioOriginal);
            } else {
                if ($img[self::h] > $img[self::hOriginal]) $img[self::h] = $img[self::hOriginal];
                $img[self::w] = (int)round($img[self::h] * $ratioOriginal);
            }
        }



        return $img;
    }




    static function alt(string $dirImage, string $imgSlug, $lang): string {
        $file = $dirImage . $imgSlug . '/' . $imgSlug . '_alt_' . $lang . '.txt';
        if (!file_exists($file)) return '';

        return file_get_contents($file);
    }




    static function resizes(string $dirImage, string $imgSlug): array {
        $files    = [];
        $dirImage .= $imgSlug;
        $glob     = glob($dirImage . '/*');
        if ($glob === false) return $files;
        foreach ($glob as $filename) {
            if (!is_file($filename)) continue;
            if (preg_match('/_[0-9_]+\w/', basename($filename), $matches)) {
                $size = trim($matches[0], '_');
                $size = str_replace('_', 'x', $size);

                $files[basename($filename)] = $size;
            }
        }
        arsort($files, SORT_NUMERIC);

        return $files;
    }




    static function slugRename(string $dirImage, string $imgSlugOld, $imgSlugNew): bool {
        if (!self::valid($dirImage, $imgSlugOld)) return false;

        $dirOld = $dirImage . $imgSlugOld . '/';
        $dirNew = $dirImage . $imgSlugNew . '/';
        $mtime  = filemtime($dirOld);

        if (!rename($dirOld, $dirNew)) {
            Flash::error('Error renaming directory');

            return false;
        }

        $glob = glob($dirNew . '*');
        if ($glob === false) return false;
        foreach ($glob as $nameOld) {
            if (!is_file($nameOld)) continue;

            $pos = strrpos($nameOld, $imgSlugOld);
            if ($pos !== false) {
                $nameNew = substr_replace($nameOld, $imgSlugNew, $pos, strlen($imgSlugOld));
                // $nameNew = str_replace($imgSlugOld, $imgSlugNew, $nameOld);

                if (!rename($nameOld, $nameNew)) {
                    Flash::error('Error renaming file:' . Text::h($nameOld) . ' -> ' . Text::h($nameNew));

                    return false;
                }
            }
        }
        if ($mtime !== false) touch($dirNew, $mtime);

        return true;
    }




    static function delete(string $dirImage, string $imgSlug): bool {
        if (!self::valid($dirImage, $imgSlug)) return false;

        foreach (new DirectoryIterator($dirImage . $imgSlug) as $fileInfo) {
            if ($fileInfo->isDot()) continue;
            unlink($fileInfo->getPathname());
        }
        rmdir($dirImage . $imgSlug);

        return true;
    }




    static function deleteResizes(string $dirImage, string $imgSlug): int {
        $resizes = self::resizes($dirImage, $imgSlug);
        $mtime   = filemtime($dirImage . $imgSlug . '/');

        foreach ($resizes as $file => $size) {
            if (!unlink($dirImage . $imgSlug . '/' . $file)) {
                Flash::error('Error removing resized image: ' . Text::h($imgSlug));
            }
        }

        if ($mtime !== false) touch($dirImage . $imgSlug . '/', $mtime);

        return count($resizes);
    }




    static function deleteWebp(string $dirImage, string $imgSlug): int {
        $resizes = self::resizes($dirImage, $imgSlug);
        $resizes = array_filter($resizes, fn($a) => str_ends_with($a, '.webp'), ARRAY_FILTER_USE_KEY);
        $mtime   = filemtime($dirImage . $imgSlug . '/');

        foreach ($resizes as $file => $size) {
            if (!unlink($dirImage . $imgSlug . '/' . $file)) {
                Flash::error('Error removing resized image: ' . Text::h($imgSlug));
            }
        }

        if ($mtime !== false) touch($dirImage . $imgSlug . '/', $mtime);

        return count($resizes);
    }




    static function render($img, $extra = ''): string {
        if (!$img || !isset($img[self::src])) return '';
        if ($img[self::version]) $img[self::src] .= '?v=' . Text::h($img[self::version]);

        $r = '';

        if ($img[self::webp] && $img[self::ext] == '.jpg') {
            $r .= '<source';
            if ($img[self::srcset]) $r .= ' srcset="' . str_replace($img[self::ext], '.webp', $img[self::srcset] ?? '') . '"';
            if ($img[self::sizes]) $r .= ' sizes="' . Text::h($img[self::sizes] ?? '') . '"';
            $r .= '>';
        }

        $r .= '<img';

        if ($img[self::lang]) {
            $r .= ' alt="' . Text::h($img[self::alt][$img[self::lang]] ?? '') . '"';
            $r .= ' lang="' . Text::h($img[self::lang]) . '"';
        } else {
            $r .= ' alt=""';
        }

        if ($img[self::loading] == 'lazy') {
            $r .= ' loading="lazy"';
        }

        $r .= ' src="' . Text::h($img[self::src] ?? '') . '"';

        if ($img[self::srcset]) $r .= ' srcset="' . Text::h($img[self::srcset] ?? '') . '"';

        if ($img[self::sizes]) $r .= ' sizes="' . Text::h($img[self::sizes] ?? '') . '"';

        if ($img[self::id]) $r .= ' id="' . Text::h($img[self::id] ?? '') . '"';

        if ($img[self::cls]) $r .= ' class="' . Text::h($img[self::cls] ?? '') . '"';

        if ($extra) $r .= ' ' . $extra;

        $r .= ' width="' . Text::h($img[self::w]) . '" height="' . Text::h($img[self::h]) . '">';

        return $r;
    }

}
