<?php
// Copyright 2017-2023 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


class ArrayShape {

    static function splicePreserveKeys(&$input, $offset, $length = null, $replacement = []): array {
        if (empty($replacement)) {
            return array_splice($input, $offset, $length);
        }

        $prefix  = array_slice($input, 0, $offset, true);
        $remove = array_slice($input, $offset, $length, true);
        $suffix   = array_slice($input, $offset + $length, null, true);

        $input = $prefix + $replacement + $suffix;

        return $remove;
    }




    static function removePermsRecursive(array &$arr, array $perms = []): void {
        foreach ($arr as $subKey => $subVal) {
            if (is_array($subVal)) {
                if (isset($subVal['gcPerms'])) {
                    if (is_string($subVal['gcPerms'])) $subVal['gcPerms'] = [$subVal['gcPerms']];

                    $foundPerms = array_intersect($subVal['gcPerms'], $perms);
                    if (!$foundPerms) {
                        $arr[$subKey] = [];
                        continue;
                    }
                    $arr[$subKey]['gcPerms'] = $foundPerms;
                }
                ArrayShape::removePermsRecursive($arr[$subKey], $perms);
            }
        }
    }




    /**
     * Walk array recursively, making keys and values multilingual.
     * Examples:
     *      keys: ['value_' => ['label' => 'Value']] becomes ['value_pt' => ['label' => 'Value'], 'value_en' => ['label' => 'Value']]
     *      values: ['slug_', 'pageId'] becomes ['slug_pt', 'slug_en', 'pageId']
     */
    static function languify(&$arr, array $langs, array $perms = []): void {
        if (!is_array($arr)) return;

        $count = count($arr);
        for ($i = $count - 1; $i >= 0; $i--) {
            $subKey = key(array_slice($arr, $i, 1, true));
            $subVal = $arr[$subKey];

            if (is_string($subVal)) {

                // languify keys with values
                if (str_ends_with($subKey, '_')) {
                    $j          = $i;
                    $subItemNew = [];
                    foreach ($langs as $lang) {
                        $subItemNew[$subKey . $lang] = $subVal;

                        $length = ($i == $j) ? 1 : 0;
                        ArrayShape::splicePreserveKeys($arr, $j, $length, $subItemNew);
                        $j++;
                    }

                    // languify values in arrays
                } else if (str_ends_with($subVal, '_')) {
                    $subItemNew = [];
                    foreach ($langs as $lang)
                        $subItemNew[] = $subVal . $lang;

                    if (is_int($subKey)) {
                        array_splice($arr, $i, 1, $subItemNew);
                    } else if (is_string($subKey)) {
                        $arr[$subKey] = $subItemNew;
                    }
                }

            } else if (is_array($subVal)) {

                // languify keys with arrays
                if (str_ends_with($subKey, '_')) {
                    $j = $i;
                    foreach ($langs as $lang) {
                        $subItemNew = [$subKey . $lang => array_merge($subVal, [
                            'lang'   => $lang,
                            'prefix' => substr($subKey, 0, -1),
                        ])];

                        $length = ($i == $j) ? 1 : 0;
                        ArrayShape::splicePreserveKeys($arr, $j, $length, $subItemNew);
                        $j++;
                    }
                } else {
                    ArrayShape::languify($arr[$subKey], $langs, $perms);
                }

            }
        }
    }

}
