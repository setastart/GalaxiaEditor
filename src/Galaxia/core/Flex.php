<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;


class Flex {

    const fixed = [
        0 => [
            '.flex { display: flex; flex-wrap: wrap; }',
            '.flex-col { display: flex; flex-direction: column; }',
            '.flex-nowrap { display: flex; flex-wrap: nowrap; }',

            '.flex-1 { flex: 1 1 0%; }',
            '.flex-auto { flex: 1 1 auto; }',
            '.flex-initial { flex: 0 1 auto; }',
            '.flex-none { flex: none; }',

            '.flex-10 { flex: 1 1 10%; }',
            '.flex-20 { flex: 1 1 20%; }',
            '.flex-30 { flex: 1 1 30%; }',
            '.flex-40 { flex: 1 1 40%; }',
            '.flex-50 { flex: 1 1 50%; }',
            '.flex-60 { flex: 1 1 60%; }',
            '.flex-70 { flex: 1 1 70%; }',
            '.flex-80 { flex: 1 1 80%; }',
            '.flex-90 { flex: 1 1 90%; }',
            '.flex-100 { flex: 1 1 100%; }',

            '.flex-nogrow { flex-grow: 0; }',
            '.flex-grow { flex-grow: 1; }',
            '.flex-noshrink { flex-shrink: 0; }',
            '.flex-shrink { flex-shrink: 1; }',

            '.order-first { order: -1; }',
            '.order-last { order: 9999; }',
            '.order-0 { order: 0; }',
        ],

        640 => [
            '.mob-flex { display: flex; flex-direction: row; }',
            '.mob-flex-col { display: flex; flex-direction: column; }',

            '.mob-order-first { order: -1; }',
            '.mob-order-last { order: 9999; }',
            '.mob-order-0 { order: 0; }',
        ],
    ];

    const combinations = [
        0 => [
            '.flex.' => [
                'h' => [
                    'justify-content' => ['stretch', 'center', 'start', 'end', 'between', 'around', 'evenly'],
                ],
                'v' => [
                    'align-items' => ['stretch', 'center', 'start', 'end', 'baseline'],
                ],
            ],

            '.flex-col.' => [
                'h' => [
                    'align-items' => ['stretch', 'center', 'start', 'end'],
                ],
                'v' => [
                    'justify-content' => ['stretch', 'center', 'start', 'end', 'between', 'around', 'evenly'],
                ],
            ],

            '.flex > .' => [
                'h-self' => [
                    'justify-self' => ['stretch', 'center', 'start', 'end', 'auto'],
                ],
                'v-self' => [
                    'align-self' => ['stretch', 'center', 'start', 'end', 'auto'],
                ],
            ],

            '.flex-col > .' => [
                'h-self' => [
                    'align-self' => ['stretch', 'center', 'start', 'end', 'auto'],
                ],
                'v-self' => [
                    'justify-self' => ['stretch', 'center', 'start', 'end', 'auto'],
                ],
            ],
        ],

        640 => [
            '.mob-flex-col.' => [
                'h' => [
                    'align-items' => ['stretch', 'center', 'start', 'end'],
                ],
                'v' => [
                    'justify-content' => ['stretch', 'center', 'start', 'end', 'between', 'around', 'evenly'],
                ],
            ],

            '.mob-flex-col > .' => [
                'h-self-auto' => [
                    'justify-self' => ['stretch', 'center', 'start', 'end', 'auto'],
                ],
                'v-self-auto' => [
                    'align-self' => ['stretch', 'center', 'start', 'end', 'baseline'],
                ],

            ],
        ],
    ];


    const modifications = [
        'stretch'  => 'stretch',
        'center'   => 'center',
        'start'    => 'flex-start',
        'end'      => 'flex-end',
        'between'  => 'space-between',
        'around'   => 'space-around',
        'evenly'   => 'space-evenly',
        'baseline' => 'baseline',
        'auto'     => 'auto',
    ];


    static function build(
        string $cssDir,
        string $htmlDir,
        string $htmlGlob = '{/,/*/,/*/*/}*.php',
        string $buildName = 'gen-flex',
        array $used = self::fixed,
        array $combs = self::combinations,
        array $mods = self::modifications
    ): string {
        AppTimer::start(__CLASS__ . '::' . __FUNCTION__);

        $cssDir  = rtrim($cssDir, '/');
        $htmlDir = rtrim($htmlDir, '/');

        $searchFiles = glob("$htmlDir$htmlGlob", GLOB_BRACE | GLOB_NOSORT);

        $css          = '';
        $cssUnused    = '';
        $classesFound = [];

        $rules = self::getRules($combs, $mods);
        self::findClasses($rules, $classesFound, $searchFiles);

        foreach ($used as $maxWidth => $items) {
            if ($maxWidth == 0) {
                foreach ($items as $item) {
                    $css .= $item . PHP_EOL;
                }
                self::drawRules($rules, $classesFound, $css, $cssUnused);
                continue;
            }
            $css       .= '@media screen and (max-width: ' . $maxWidth . 'px) {' . PHP_EOL;
            $cssUnused .= '@media screen and (max-width: ' . $maxWidth . 'px) {' . PHP_EOL;

            foreach ($items as $item) {
                $css .= '    ' . $item . PHP_EOL;
            }
            self::drawRules($rules, $classesFound, $css, $cssUnused, '    ');

            $css       .= '}' . PHP_EOL;
            $cssUnused .= '}' . PHP_EOL;
        }

        file_put_contents("$cssDir/$buildName.css", $css);
        file_put_contents("$cssDir/$buildName-unused.css", $cssUnused);

        AppTimer::stop(__CLASS__ . '::' . __FUNCTION__);

        if (G::isDevEnv()) return $css . Text::commentHeader('Unused') . $cssUnused;

        return $css;
    }




    private static function getRules(array $combinations, array $modifications): array {
        foreach ($combinations as $maxWidth => $prefixes) {
            foreach ($prefixes as $prefix => $classes) {
                foreach ($classes as $start => $properties) {
                    foreach ($properties as $property => $mods) {
                        foreach ($mods as $mod) {
                            $class = $start . '-' . $mod;

                            $rules[$prefix][$class] = $property . ': ' . $modifications[$mod];
                        }
                    }
                }
            }
        }

        return $rules ?? [];
    }


    private static function findClasses(array $rules, array &$classesFound, array $htmlFiles): void {
        $fileContents = [];
        foreach ($htmlFiles as $file) {
            $fileContents[$file] = file_get_contents($file);
        }

        foreach ($rules as $prefix => $classes) {
            foreach ($classes as $class => $rule) {
                if (isset($classesFound[$class])) continue;

                foreach ($fileContents as $file) {
                    if (preg_match('~["\' ]' . $class . '["\' ]~m', $file)) {
                        $classesFound[$class] = true;
                        break;
                    }
                }
            }
        }
    }

    private static function drawRules(
        array $rules,
        array $classesFound,
        string &$css,
        string &$cssUnused,
        string $indent = ''
    ): void {
        foreach ($rules as $prefix => $classes) {
            foreach ($classes as $class => $rule) {
                $complete = $indent . $prefix . $class . ' { ' . $rule . '; }' . PHP_EOL;
                if (isset($classesFound[$class])) {
                    $css .= $complete;
                } else {
                    $cssUnused .= $complete;
                }
            }
        }
    }

}
