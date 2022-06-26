<?php
// Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


use Exception;


class ImageVips {

    const EXT_JPG  = '.jpg';
    const EXT_PNG  = '.png';
    const EXT_WEBP = '.webp';

    const FORMATS = [self::EXT_JPG, self::EXT_PNG];

    const LOADERS = ['jpegload' => self::EXT_JPG, 'pngload' => self::EXT_PNG];

    public static int $qualityJpg    = 85;
    public static int $qualityWebp   = 88;
    public static int $qualityPngMin = 70;
    public static int $qualityPngMax = 90;

    public mixed   $vips    = null;
    public bool    $fromUrl = false;
    public string  $in;
    public ?string $ext;
    public int     $w, $h;
    public bool    $resized;


    /**
     * @param string $in - Path to uploaded temp file using POST or URL to a valid image to be downloaded
     * @throws Exception
     */
    function __construct(string $in) {
        if (filter_var($in, FILTER_VALIDATE_URL)) {
            $temp = tempnam(sys_get_temp_dir(), 'GalaxiaImageFromUrl_');
            if ($temp === false) throw new Exception('Could not create temp file for image from URL.');

            file_put_contents($temp, file_get_contents($in));
            if (file_put_contents($temp, file_get_contents($in)) === false) throw new Exception('Could not write to temp file for image from URL.');

            $in            = $temp;
            $this->fromUrl = true;
        }

        $this->in   = $in;
        $this->vips = vips_image_new_from_file($this->in)['out'] ?? false;
        if (!$this->vips) throw new Exception('Could not load vips image.');

        $loader = vips_image_get($this->vips, 'vips-loader')['out'] ?? false;
        if (!$loader) throw new Exception('Could not load vips file loader.');

        $this->ext = self::LOADERS[$loader] ?? false;
        if (!$this->ext) throw new Exception('Could not load vips file format.');

        $this->w = vips_image_get($this->vips, 'width')['out'] ?? 0;
        $this->h = vips_image_get($this->vips, 'height')['out'] ?? 0;
        if (!$this->w || !$this->h) throw new Exception('Could not read vips image dimensions.');
    }


    /**
     * @throws Exception
     */
    function save(string $outWithoutExt, bool $overwrite = false, int $toFit = 0): void {
        if ($toFit > 0 && ($this->w > $toFit || $this->h > $toFit)) {
            if ($img = AppImage::fit(array_merge(AppImage::proto, ['wOriginal' => $this->w, 'hOriginal' => $this->h, 'w' => $toFit, 'h' => $toFit]))) {
                $this->w = $img['w'];
                $this->h = $img['h'];

                $this->vips = vips_call('thumbnail', null, $this->in, $this->w, ['height' => $this->h])['out'] ?? false;
                if ($this->vips) {
                    $this->resized = true;
                } else {
                    Flash::devlog('Could not resize to fit - vips thumbnail.');
                }
            }
        }

        try {
            self::write($this->vips, $this->ext, $outWithoutExt . $this->ext, $overwrite);
        } catch (Exception $e) {
            Flash::devlog($e->getMessage());
        }

    }


    /**
     * @throws Exception
     */
    static function crop(string $dir, string $slug, string $ext, int $w, int $h, bool $overwite = false, bool $debug = false, bool $webp = false): void {
        if ($w <= 0) return;

        $in = $dir . $slug . $ext;

        if ($h >= 0) $options = ['height' => $h];
        $options['crop'] = 'centre';

        $vips = vips_call('thumbnail', null, $in, $w, $options)['out'] ?? false;

        $w = vips_image_get($vips, 'width')['out'] ?? 0;
        $h = vips_image_get($vips, 'height')['out'] ?? 0;

        if ($webp) {
            $out    = $dir . $slug . '_' . $w . '_' . $h . self::EXT_WEBP;
            $outExt = self::EXT_WEBP;
        } else {
            $out    = $dir . $slug . '_' . $w . '_' . $h . $ext;
            $outExt = $ext;
        }

        if ($debug) {
            $textW = min($w, 120);
            $textH = min($h, 60);
            $text  = vips_call('text', null, "{$outExt} {$w}x{$h}", ['width' => $textW, 'height' => $textH])['out'];
            $vips  = vips_call('composite', null, [$vips, $text], 2, ['x' => [$w / 2 - ($textW / 2)], 'y' => [$h / 2]])['out'];
        }

        if (!$vips) {
            throw new Exception('Could not vips thumbnail.');
        }


        if ($overwite) {
            $out = $in;
        }

        try {
            self::write($vips, $outExt, $out, $overwite);
        } catch (Exception $e) {
            Flash::devlog($e->getMessage());
        }

        if ($ext == self::EXT_PNG) {
            try {
                $compressed = self::compressPng($out);
                if ($compressed) {
                    file_put_contents($out, $compressed);
                } else {
                    Flash::devlog('Could not pngquant image.');
                }
            } catch (Exception $e) {
                Flash::devlog($e->getMessage());
            }
        }
    }


    /**
     * @throws Exception
     */
    private static function write($vips, $ext, $out, $overwite): void {
        $outOriginal = $out;
        if ($overwite) {
            $out .= '.temp' . $ext;
        }
        switch ($ext) {
            case self::EXT_JPG:
                $success = vips_image_write_to_file($vips, $out, [
                    'Q'               => self::$qualityJpg,
                    'quant_table'     => 2,
                    'optimize_coding' => true,
                ]);
                if ($success == -1) {
                    throw new Exception('Could not write .jpg');
                }
                break;

            case self::EXT_WEBP:
                $success = vips_image_write_to_file($vips, $out, [
                    'Q'                => self::$qualityWebp,
                    'reduction_effort' => 6,
                    'smart_subsample'  => true,
                ]);
                if ($success == -1) {
                    throw new Exception('Could not write .webp');
                }
                break;

            default:
                $success = vips_image_write_to_file($vips, $out, [
                    'Q' => self::$qualityJpg,
                ]);
                if ($success == -1) throw new Exception('Could not write .png');
                break;
        }

        if ($overwite) {
            rename($out, $outOriginal);
        }

    }


    /**
     * Optimizes PNG file with pngquant 1.8 or later (reduces file size of 24-bit/32-bit PNG images).
     *
     * You need to install pngquant 1.8 on the server (ancient version 1.0 won't work).
     * There's package for Debian/Ubuntu and RPM for other distributions on http://pngquant.org
     *
     * @param $path_to_png_file string - path to any PNG file, e.g. $_FILE['file']['tmp_name']
     * @return string - content of PNG file after conversion
     * @throws Exception
     */
    static function compressPng(string $path_to_png_file): string {
        if (!file_exists($path_to_png_file)) {
            throw new Exception('File does not exist: ' . $path_to_png_file);
        }

        $min = self::$qualityPngMin;
        $max = self::$qualityPngMax;

        // '-' makes it use stdout, required to save to $compressed variable
        // '<' makes it read from the given file path
        $cmd = "pngquant --quality=$min-$max - < " . escapeshellarg($path_to_png_file);

        $compressed = shell_exec($cmd) ?? '';


        if (!$compressed) {
            $ver = shell_exec('pngquant --version') ?? '';
            geD(
                'pngquant --version: ' . $ver,
                'command: ' . $cmd
            );
            throw new Exception('Conversion to compressed PNG failed. Is pngquant 1.8+ installed on the server?');
        }

        return $compressed;
    }

}
