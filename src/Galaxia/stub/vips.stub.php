<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12


// from https://github.com/libvips/php-vips-ext/blob/master/vips.c

/** @noinspection PhpInconsistentReturnPointsInspection */
function vips_image_new_from_file(string $filename, array $options = []): mixed {}
function vips_image_new_from_buffer(string $buffer, string $option_string = '', array $options = []): mixed {}
function vips_image_new_from_array(array $array, float $scale, float $offset): mixed {}
function vips_interpolate_new(string $name): mixed {}
function vips_image_write_to_file($image, string $filename, array $options = []): mixed {}
function vips_image_write_to_buffer($image, $options): mixed {}
function vips_image_copy_memory($image): mixed {}
function vips_image_new_from_memory($array, $width, $height, $bands, $format): mixed {}
function vips_image_write_to_memory($image): mixed {}
function vips_image_write_to_array($image): mixed {}
function vips_foreign_find_load($filename): mixed {}
function vips_foreign_find_load_buffer($buffer): mixed {}
function vips_call($operation_name, $instance, ...$more): mixed {}
function vips_image_get($image, $field): mixed {}
function vips_image_get_typeof($image, $field): mixed {}
function vips_image_set($image, $field, $value): mixed {}
function vips_type_from_name($name): mixed {}
function vips_image_set_type($image, $type, $field, $value): mixed {}
function vips_image_remove($image, $field): mixed {}
function vips_error_buffer(): mixed {}
function vips_cache_set_max($value): mixed {}
function vips_cache_set_max_mem($value): mixed {}
function vips_cache_set_max_files($value): mixed {}
function vips_concurrency_set($value): mixed {}
function vips_cache_get_max(): mixed {}
function vips_cache_get_max_mem(): mixed {}
function vips_cache_get_max_files(): mixed {}
function vips_cache_get_size(): mixed {}
function vips_concurrency_get(): mixed {}
function vips_version(): mixed {}


// $r = '';
// $ext = new ReflectionExtension('vips');
//
// foreach ($ext->getFunctions() as $function) {
//     $ref = new ReflectionFunction($function->name);
//     $params = [];
//     foreach ($ref->getParameters() as $param) $params[] = '$' . $param->getName();
//
//     $r .= "function $function->name (" . implode(', ', $params) . ') {}' . PHP_EOL;
// }
// exit($r);
