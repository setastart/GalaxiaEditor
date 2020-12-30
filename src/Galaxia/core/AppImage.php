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


use DirectoryIterator;


class AppImage {

    public const PROTO_IMAGE = [
        'name'      => '',
        'ext'       => '',
        'mtime'     => '',
        'fileSize'  => 0,
        'w'         => 0,
        'h'         => 0,
        'wOriginal' => 0,
        'hOriginal' => 0,
        'fit'       => 'contain',
        'version'   => '',
        'id'        => '',
        'class'     => '',
        'src'       => '',
        'set'       => [],
        'srcset'    => '',
        'sizes'     => '',
        'alt'       => [],
        'lang'      => '',
        'extra'     => [],
        'loading'   => 'lazy',
        'debug'     => false,
    ];




    static function list($dirImage): array {
        $images = [];
        $glob   = glob($dirImage . '*', GLOB_NOSORT);
        if ($glob === false) return $images;
        foreach ($glob as $filename) {
            if (!is_dir($filename)) continue;
            $basename          = basename($filename);
            $images[$basename] = [
                'name' => $basename,
                'time' => filemtime($filename),
            ];
        }

        uasort($images, function($a, $b) {
            if ($a['time'] == $b['time']) return strnatcmp($a['name'], $b['name']);

            return $b['time'] <=> $a['time'];
        });

        foreach ($images as $key => $image) {
            $images[$key] = $image['time'];
        }

        return $images;
    }




    static function valid(string $dirImage, string $imgSlug) {
        if (empty($imgSlug)) return false;
        if (preg_match('/[^a-z0-9-]/', $imgSlug)) return false;
        if (realpath($dirImage) == realpath($dirImage . $imgSlug)) return false;
        if (!is_dir($dirImage . $imgSlug)) return false;

        $fileBase = $dirImage . $imgSlug . '/' . $imgSlug;
        if (file_exists($fileBase . '.jpg')) return '.jpg';
        if (file_exists($fileBase . '.png')) return '.png';

        return false;
    }




    static function dimensions(string $dirImage, string $imgSlug): array {
        $file = $dirImage . $imgSlug . '/' . $imgSlug . '_dim.txt';
        if (!file_exists($file)) return [];
        $dim = explode('x', file_get_contents($file));

        return ($dim) ? [(int)$dim[0], (int)$dim[1]] : [];
    }




    static function fit($img): array {
        $img['wOriginal'] = (int)$img['wOriginal'];
        $img['hOriginal'] = (int)$img['hOriginal'];

        if ($img['w'] == 0 && $img['h'] == 0) {
            $img['w'] = $img['wOriginal'];
            $img['h'] = $img['hOriginal'];

            return $img;
        }

        $ratioOriginal = $img['wOriginal'] / $img['hOriginal'];

        if ($img['w'] == 0) {
            if ($img['h'] > $img['hOriginal']) $img['h'] = $img['hOriginal'];
            $img['w'] = (int)round($img['h'] * $ratioOriginal);

            return $img;
        }

        if ($img['h'] == 0) {
            if ($img['w'] > $img['wOriginal']) $img['w'] = $img['wOriginal'];
            $img['h'] = (int)round($img['w'] / $ratioOriginal);

            return $img;
        }

        if (!in_array($img['fit'] ?? '', ['contain', 'cover', 'expand'])) $img['fit'] = 'contain';

        $ratioFit = $img['w'] / $img['h'];

        if ($img['fit'] == 'contain') {
            if ($ratioFit > $ratioOriginal) {
                if ($img['h'] > $img['hOriginal']) $img['h'] = $img['hOriginal'];
                $img['w'] = (int)round($img['h'] * $ratioOriginal);
            } else {
                if ($img['w'] > $img['wOriginal']) $img['w'] = $img['wOriginal'];
                $img['h'] = (int)round($img['w'] / $ratioOriginal);
            }
        } else if ($img['fit'] == 'cover') {
            if ($img['w'] > $img['wOriginal']) {
                $img['w'] = $img['wOriginal'];
                $img['h'] = (int)round($img['w'] / $ratioFit);
            }

            if ($img['h'] > $img['hOriginal']) {
                $img['h'] = $img['hOriginal'];
                $img['w'] = (int)round($img['h'] * $ratioFit);
            }
        } else {
            if ($ratioFit > $ratioOriginal) {
                if ($img['w'] > $img['wOriginal']) $img['w'] = $img['wOriginal'];
                $img['h'] = (int)round($img['w'] / $ratioOriginal);
            } else {
                if ($img['h'] > $img['hOriginal']) $img['h'] = $img['hOriginal'];
                $img['w'] = (int)round($img['h'] * $ratioOriginal);
            }
        }



        return $img;
    }




    static function alt(string $dirImage, string $imgSlug, $lang) {
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
                $size         = trim($matches[0], '_');
                $size         = str_replace('_', 'x', $size);
                $files[$size] = basename($filename);
            }
        }
        krsort($files, SORT_NUMERIC);

        return $files;
    }




    static function slugRename(string $dirImage, string $imgSlugOld, $imgSlugNew): bool {
        if (!AppImage::valid($dirImage, $imgSlugOld)) return false;

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
        if (!AppImage::valid($dirImage, $imgSlug)) return false;

        foreach (new DirectoryIterator($dirImage . $imgSlug) as $fileInfo) {
            if ($fileInfo->isDot()) continue;
            unlink($fileInfo->getPathname());
        }
        rmdir($dirImage . $imgSlug);

        return true;
    }




    static function deleteResizes(string $dirImage, string $imgSlug): int {
        $resizes = AppImage::resizes($dirImage, $imgSlug);
        $mtime   = filemtime($dirImage . $imgSlug . '/');

        foreach ($resizes as $file) {
            if (!unlink($dirImage . $imgSlug . '/' . $file)) {
                Flash::error('Error removing resized image: ' . Text::h($imgSlug));
            }
        }

        if ($mtime !== false) touch($dirImage . $imgSlug . '/', $mtime);

        return count($resizes);
    }




    static function render($img, $extra = ''): string {
        if (!$img || !isset($img['src'])) return '';
        if ($img['version']) $img['src'] .= '?v=' . Text::h($img['version']);
        $r = '<img';

        if ($img['lang']) {
            $r .= ' alt="' . Text::h($img['alt'][$img['lang']] ?? '') . '"';
            $r .= ' lang="' . Text::h($img['lang']) . '"';
        } else {
            $r .= ' alt=""';
        }

        if ($img['loading'] == 'lazy') {
            $r .= ' loading="lazy"';
        }

        $r .= ' src="' . Text::h($img['src'] ?? '') . '"';

        if ($img['srcset']) $r .= ' srcset="' . Text::h($img['srcset'] ?? '') . '"';

        if ($img['sizes']) $r .= ' sizes="' . Text::h($img['sizes'] ?? '') . '"';

        if ($img['id']) $r .= ' id="' . Text::h($img['id'] ?? '') . '"';

        if ($img['class']) $r .= ' class="' . Text::h($img['class'] ?? '') . '"';

        if ($extra) $r .= ' ' . $extra;

        $r .= ' width="' . Text::h($img['w']) . '" height="' . Text::h($img['h']) . '">';

        return $r;
    }

}
