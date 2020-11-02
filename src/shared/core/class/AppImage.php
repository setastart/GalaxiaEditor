<?php


namespace Galaxia;


use DirectoryIterator;


class AppImage {

    public const PROTO_IMAGE = [
        'fit'         => '',
        'w'           => 0,
        'h'           => 0,
        'wOriginal'   => 0,
        'hOriginal'   => 0,
        'name'        => '',
        'ext'         => '',
        'mtime'       => '',
        'fileSize'    => 0,
        'version'     => '',
        'src'         => '',
        'srcset'      => '',
        'alt'         => [],
        'lang'        => '',
        'extra'       => [],
        'sizes'       => [1],
        'sizeDivisor' => 1,
        'loading'     => 'lazy',
    ];




    public static function list($dirImage) {
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




    public static function valid(string $dirImage, string $imgSlug) {
        if (empty($imgSlug)) return false;
        if (preg_match('/[^a-z0-9-]/', $imgSlug)) return false;
        if (realpath($dirImage) == realpath($dirImage . $imgSlug)) return false;
        if (!is_dir($dirImage . $imgSlug)) return false;

        $fileBase = $dirImage . $imgSlug . '/' . $imgSlug;
        if (file_exists($fileBase . '.jpg')) return '.jpg';
        if (file_exists($fileBase . '.png')) return '.png';

        return false;
    }




    public static function dimensions(string $dirImage, string $imgSlug) {
        $file = $dirImage . $imgSlug . '/' . $imgSlug . '_dim.txt';
        if (!file_exists($file)) return [];
        $dim = explode('x', file_get_contents($file));

        return ($dim) ? [(int)$dim[0], (int)$dim[1]] : [];
    }




    public static function fit($img) {
        $ratioOriginal = $img['wOriginal'] / $img['hOriginal'];

        if ($img['fit'] && is_int($img['w']) && is_int($img['h']) && $img['w'] > 0 && $img['h'] > 0) {
            if ($img['fit'] == 'cover') {
                $ratioFit = $img['w'] / $img['h'];
                if ($ratioFit >= $ratioOriginal) {
                    $img['w'] = 0;
                } else if ($ratioFit < $ratioOriginal) {
                    $img['h'] = 0;
                }
            } else if ($img['fit'] == 'contain') {
                $ratioFit = $img['w'] / $img['h'];
                if ($ratioFit >= $ratioOriginal) {
                    $img['h'] = 0;
                } else if ($ratioFit < $ratioOriginal) {
                    $img['w'] = 0;
                }
            }
        }

        if ($img['w'] < 0 || $img['h'] < 0) return [];
        if ($img['w'] == 0) {
            if ($img['h'] == 0) {
                $img['w'] = (int)$img['wOriginal'];
                $img['h'] = (int)$img['hOriginal'];
            } else {
                if ($img['h'] > $img['hOriginal']) $img['h'] = $img['hOriginal'];
                $img['w'] = (int)round($img['h'] * $ratioOriginal);
            }
        } else {
            if ($img['w'] > $img['wOriginal']) $img['w'] = $img['wOriginal'];
            if ($img['h'] == 0) $img['h'] = (int)round($img['w'] / $ratioOriginal);
        }

        return $img;
    }




    public static function alt(string $dirImage, string $imgSlug, $lang) {
        $file = $dirImage . $imgSlug . '/' . $imgSlug . '_alt_' . $lang . '.txt';
        if (!file_exists($file)) return '';

        return file_get_contents($file);
    }




    public static function resizes(string $dirImage, string $imgSlug) {
        $files    = [];
        $dirImage = $dirImage . $imgSlug;
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




    public static function slugRename(string $dirImage, string $imgSlugOld, $imgSlugNew) {
        if (!self::valid($dirImage, $imgSlugOld)) return false;

        $dirOld = $dirImage . $imgSlugOld . '/';
        $dirNew = $dirImage . $imgSlugNew . '/';
        $mtime  = filemtime($dirOld);

        if (!rename($dirOld, $dirNew)) {
            error('Error renaming directory');

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
                    error('Error renaming file:' . h($nameOld) . ' -> ' . h($nameNew));

                    return false;
                }
            }
        }
        if ($mtime !== false) touch($dirNew, $mtime);

        return true;
    }




    public static function delete(string $dirImage, string $imgSlug) {
        if (!self::valid($dirImage, $imgSlug)) return false;

        foreach (new DirectoryIterator($dirImage . $imgSlug) as $fileInfo) {
            if ($fileInfo->isDot()) continue;
            unlink($fileInfo->getPathname());
        }
        rmdir($dirImage . $imgSlug);

        return true;
    }




    public static function deleteResizes(string $dirImage, string $imgSlug) {
        $resizes = self::resizes($dirImage, $imgSlug);
        $mtime   = filemtime($dirImage . $imgSlug . '/');

        foreach ($resizes as $file) {
            if (!unlink($dirImage . $imgSlug . '/' . $file)) {
                error('Error removing resized image: ' . h($imgSlug));
            }
        }

        if ($mtime !== false) touch($dirImage . $imgSlug . '/', $mtime);

        return count($resizes);
    }




    public static function render($img, $extra = '') {
        if (!$img || !isset($img['src'])) return '';
        if ($img['version']) $img['src'] .= '?v=' . h($img['version']);
        $r = '<img';

        if ($img['lang']) {
            $r .= ' alt="' . h($img['alt'][$img['lang']] ?? '') . '"';
            $r .= ' lang="' . h($img['lang']) . '"';
        } else {
            $r .= ' alt=""';
        }

        if ($img['loading'] == 'lazy') {
            $r .= ' loading="lazy"';
        }

        $r .= ' src="' . h($img['src'] ?? '') . '"';
        if ($img['srcset']) $r .= ' srcset="' . h($img['srcset'] ?? '') . '"';

        if ($extra) $r .= ' ' . $extra;

        $r .= ' width="' . h($img['w']) . '" height="' . h($img['h']) . '">';

        return $r;
    }

}
