<?php
/* Copyright 2017-2021 Ino Detelić & Zaloa G. Ramos

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
use Exception;


class AppImage {

    public const PROTO_IMAGE = [
        'name'      => '',
        'slug'      => '',
        'ext'       => '',
        'webp'      => false,
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




    // images

    static function imageGet(App $app, $imgSlug, $img = [], $resize = true): array {
        $img = array_merge(AppImage::PROTO_IMAGE, $img);

        $img['name'] = $app->urlImages . $imgSlug . '/' . $imgSlug;
        $img['slug'] = $imgSlug;

        if (!$img['ext'] = AppImage::valid($app->dirImage, $imgSlug)) return [];
        $imgDir     = $app->dirImage . $imgSlug . '/';
        $imgDirSlug = $imgDir . $imgSlug;


        // modified time
        $img['mtime'] = filemtime($imgDir);
        if ($img['version'] == 'mtime') $img['version'] = $img['mtime'];


        // file size
        if ($img['fileSize']) $img['fileSize'] = filesize($imgDirSlug . $img['ext']);


        // alt
        foreach ($app->langs as $lang) {
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
                foreach ($app->langs as $lang) {
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

            $file = $imgDirSlug . '_' . $img['w'] . '_' . $img['h'] . '.webp';
            if ($img['webp'] && !file_exists($file)) {
                File::lock(
                    $app->dirCache . 'flock',
                    '_img_' . $imgSlug . '_' . $img['w'] . '_' . $img['h'] . '.webp' . '.lock',
                    function() use ($imgDir, $imgSlug, $img) {
                        try {
                            ImageVips::crop($imgDir, $imgSlug, $img['ext'], $img['w'], $img['h'], false, $img['debug'], true);
                        } catch (Exception $e) {
                            d($e->getMessage(), $e->getTraceAsString());
                        }
                        touch($imgDir, $img['mtime']);
                    }
                );
            }

            $img['src'] = $img['name'] . $img['ext'];

        } else {

            $file = $imgDirSlug . '_' . $img['w'] . '_' . $img['h'] . $img['ext'];
            if ($resize && !file_exists($file)) {
                File::lock(
                    $app->dirCache . 'flock',
                    '_img_' . $imgSlug . '_' . $img['w'] . '_' . $img['h'] . $img['ext'] . '.lock',
                    function() use ($imgDir, $imgSlug, $img) {
                        try {
                            ImageVips::crop($imgDir, $imgSlug, $img['ext'], $img['w'], $img['h'], false, $img['debug']);
                        } catch (Exception $e) {
                            d($e->getMessage(), $e->getTraceAsString());
                        }
                        touch($imgDir, $img['mtime']);
                    }
                );
            }

            $file = $imgDirSlug . '_' . $img['w'] . '_' . $img['h'] . '.webp';
            if ($img['webp'] && $resize && !file_exists($file)) {
                File::lock(
                    $app->dirCache . 'flock',
                    '_img_' . $imgSlug . '_' . $img['w'] . '_' . $img['h'] . '.webp' . '.lock',
                    function() use ($imgDir, $imgSlug, $img) {
                        try {
                            ImageVips::crop($imgDir, $imgSlug, $img['ext'], $img['w'], $img['h'], false, $img['debug'], true);
                        } catch (Exception $e) {
                            d($e->getMessage(), $e->getTraceAsString());
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

                $file = $imgDirSlug . '_' . $imgResize['w'] . '_' . $imgResize['h'] . '.webp';
                if ($img['webp'] && $resize && !file_exists($file)) {
                    File::lock(
                        $app->dirCache . 'flock',
                        '_img_' . $imgSlug . '_' . $imgResize['w'] . '_' . $imgResize['h'] . '.webp' . '.lock',
                        function() use ($imgDir, $imgSlug, $imgResize, $img) {
                            try {
                                if ($img['webp']) ImageVips::crop($imgDir, $imgSlug, $imgResize['ext'], $imgResize['w'], $imgResize['h'], false, $imgResize['debug'], true);
                            } catch (Exception $e) {
                                d($e->getMessage(), $e->getTraceAsString());
                            }
                            touch($imgDir, $imgResize['mtime']);
                        }
                    );
                }

                $img['srcset'] .= $img['name'] . $img['ext'] . ' ' . $setDescriptor . ', ';
            } else {

                $file = $imgDirSlug . '_' . $imgResize['w'] . '_' . $imgResize['h'] . $img['ext'];
                if ($resize && !file_exists($file)) {
                    File::lock(
                        $app->dirCache . 'flock',
                        '_img_' . $imgSlug . '_' . $imgResize['w'] . '_' . $imgResize['h'] . $img['ext'] . '.lock',
                        function() use ($imgDir, $imgSlug, $imgResize, $img) {
                            try {
                                ImageVips::crop($imgDir, $imgSlug, $imgResize['ext'], $imgResize['w'], $imgResize['h'], false, $imgResize['debug']);
                            } catch (Exception $e) {
                                d($e->getMessage(), $e->getTraceAsString());
                            }
                            touch($imgDir, $imgResize['mtime']);
                        }
                    );
                }

                $file = $imgDirSlug . '_' . $imgResize['w'] . '_' . $imgResize['h'] . '.webp';
                if ($img['webp'] && $resize && !file_exists($file)) {
                    File::lock(
                        $app->dirCache . 'flock',
                        '_img_' . $imgSlug . '_' . $imgResize['w'] . '_' . $imgResize['h'] . '.webp' . '.lock',
                        function() use ($imgDir, $imgSlug, $imgResize, $img) {
                            try {
                                ImageVips::crop($imgDir, $imgSlug, $imgResize['ext'], $imgResize['w'], $imgResize['h'], false, $imgResize['debug'], true);
                            } catch (Exception $e) {
                                d($e->getMessage(), $e->getTraceAsString());
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


    static function imageUpload(App $app, array $files, $replaceDefault = false, int $toFitDefault = 0, string $type = ''): array {
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
            $fileDir    = $app->dirImage . $fileSlug . '/';
            $dirCreated = false;
            if (is_dir($app->dirImage . $fileSlug)) {
                if ($fileReplace) {
                    $mtime         = filemtime($app->dirImage . $fileSlug . '/');
                    $shouldReplace = true;
                } else {
                    for ($j = 0; $j < 3; $j++) {
                        if (!is_dir($app->dirImage . $fileSlug)) break;
                        $fileSlug = Text::formatSlug('temp' . uniqid() . '-' . $fileSlugInitial);
                        $fileDir  = $app->dirImage . $fileSlug . '/';
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
                if ($dirCreated) AppImage::delete($app->dirImage, $fileSlug);
                if ($dirCreated) rmdir($fileDir);
                continue;
            }


            if ($shouldReplace) {
                foreach (ImageVips::FORMATS as $format) {
                    if ($format == $imageVips->ext) continue;
                    if (file_exists($fileDir . $fileSlug . $format)) unlink($fileDir . $fileSlug . $format);
                }
                AppImage::deleteResizes($app->dirImage, $fileSlug);
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
                    touch($app->dirImage . $fileSlug . '/', $mtime);
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
                $size = trim($matches[0], '_');
                $size = str_replace('_', 'x', $size);

                $files[basename($filename)] = $size;
            }
        }
        arsort($files, SORT_NUMERIC);

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

        foreach ($resizes as $file => $size) {
            if (!unlink($dirImage . $imgSlug . '/' . $file)) {
                Flash::error('Error removing resized image: ' . Text::h($imgSlug));
            }
        }

        if ($mtime !== false) touch($dirImage . $imgSlug . '/', $mtime);

        return count($resizes);
    }




    static function deleteWebp(string $dirImage, string $imgSlug): int {
        $resizes = AppImage::resizes($dirImage, $imgSlug);
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
        if (!$img || !isset($img['src'])) return '';
        if ($img['version']) $img['src'] .= '?v=' . Text::h($img['version']);

        $r = '';

        if ($img['webp'] && $img['ext'] == '.jpg') {
            $r .= '<source';
            if ($img['srcset']) $r .= ' srcset="' . str_replace($img['ext'], '.webp', $img['srcset'] ?? '') . '"';
            if ($img['sizes']) $r .= ' sizes="' . Text::h($img['sizes'] ?? '') . '"';
            $r .= '>';
        }

        $r .= '<img';

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
