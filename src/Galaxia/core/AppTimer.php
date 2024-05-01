<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;

class AppTimer {

    private static array $timers      = [];
    private static int   $timerLevel  = 0;
    private static int   $timerMaxLen = 0;
    private static int   $timerMaxLev = 0;

    static function start(string $timerLabel, $timeFloat = null): void {
        // if (isset(G::$me) && !G::isDev()) return;

        if (isset(AppTimer::$timers[$timerLabel])) {
            if (AppTimer::$timers[$timerLabel]['running']) return;
            AppTimer::$timers[$timerLabel]['lap']     = microtime(true);
            AppTimer::$timers[$timerLabel]['running'] = true;
            AppTimer::$timers[$timerLabel]['mem']     = memory_get_usage(false);
        } else {
            AppTimer::$timerLevel++;
            AppTimer::$timers[$timerLabel]        = [
                'start'   => $timeFloat ?? microtime(true),
                'end'     => 0,
                'level'   => AppTimer::$timerLevel,
                'running' => true,
                'total'   => 0,
                'lap'     => $timeFloat ?? 0,
                'count'   => 0,
                'mem'     => memory_get_usage(false),
            ];
            AppTimer::$timers[$timerLabel]['lap'] = AppTimer::$timers[$timerLabel]['start'];
            AppTimer::$timerMaxLen                = max(AppTimer::$timerMaxLen, (AppTimer::$timerLevel * 2) + strlen($timerLabel));
            AppTimer::$timerMaxLev                = max(AppTimer::$timerMaxLev, AppTimer::$timerLevel);
        }
    }


    static function mark(string $timerLabel): void {
        // if (isset(G::$me) && !G::isDev()) return;
        $timerLabel                           = '! ' . $timerLabel;
        $now                                  = microtime(true);
        AppTimer::$timers[$timerLabel]['start']   = $now;
        AppTimer::$timers[$timerLabel]['end']     = $now;
        AppTimer::$timers[$timerLabel]['level']   = AppTimer::$timerLevel + 1;
        AppTimer::$timers[$timerLabel]['total']   = 0;
        AppTimer::$timers[$timerLabel]['running'] = false;
        AppTimer::$timers[$timerLabel]['lap']     = $now;
        AppTimer::$timers[$timerLabel]['count']   = 0;
        AppTimer::$timers[$timerLabel]['mem']     = memory_get_usage(false);
    }


    static function stop(string $timerLabel, string $rename = ''): void {
        // if (isset(G::$me) && !G::isDev()) return;

        if (!isset(AppTimer::$timers[$timerLabel])) return;
        // if (!AppTimer::$timers[$timerLabel]['running']) return;

        if (AppTimer::$timerLevel > 0) AppTimer::$timerLevel--;
        AppTimer::$timers[$timerLabel]['end']     = microtime(true);
        AppTimer::$timers[$timerLabel]['total']   += AppTimer::$timers[$timerLabel]['end'] - AppTimer::$timers[$timerLabel]['lap'];
        AppTimer::$timers[$timerLabel]['running'] = false;
        AppTimer::$timers[$timerLabel]['lap']     = 0;
        AppTimer::$timers[$timerLabel]['count']++;
        AppTimer::$timers[$timerLabel]['mem'] = memory_get_usage(false) - AppTimer::$timers[$timerLabel]['mem'];

        if ($rename) AppTimer::$timers[$timerLabel]['rename'] = $rename;
    }


    static function print(
        bool $comments = false,
        bool $memory = false,
        bool $includes = false,
        bool $force = false,
    ): void {
        if (!$force && !G::isCli() && !G::isDevEnv() && !G::isDev()) return;

        $timeEnd = microtime(true);

        AppTimer::$timers['Total']['end']     = $timeEnd;
        AppTimer::$timers['Total']['total']   += AppTimer::$timers['Total']['end'] - AppTimer::$timers['Total']['lap'];
        AppTimer::$timers['Total']['running'] = false;
        AppTimer::$timers['Total']['lap']     = 0;
        AppTimer::$timers['Total']['count']++;
        AppTimer::$timers['Total']['mem'] = memory_get_usage(false) - AppTimer::$timers['Total']['mem'];

        $r       = '';
        $prefix  = '';
        $postfix = PHP_EOL;
        $padHead = ' ';
        $pad     = ' ';

        if ($comments) {
            $prefix  = '<!-- ';
            $postfix = ' -->' . PHP_EOL;
            $pad     = '`';
            $padHead = '.';
        }

        $timeTotal   = AppTimer::$timers['Total']['total'];
        $levelTotals = [$timeTotal];

        $cols   = [];
        $colLen = [];

        foreach (AppTimer::$timers as $timerLabel => $time) {
            $percentOfParent             = '';
            $levelTotals[$time['level']] = $time['total'];

            if ($time['level'] > 0) {
                $divisor = $levelTotals[$time['level'] - 1];
                if ($divisor == 0) $divisor = 1;
                $percentOfParent = (($time['total'] * 100) / $divisor);
                $percentOfParent = number_format($percentOfParent);
            }

            // if ($percentOfParent > 99) $percentOfParent = 99;

            $cols[$timerLabel]['start'] = number_format(($time['start'] - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 3);
            $cols[$timerLabel]['#']     = $time['count'];
            if ($time['running']) {
                $cols[$timerLabel]['time'] = number_format(($timeEnd - $time['start']) * 1000, 2);
            } else {
                $cols[$timerLabel]['time'] = number_format($time['total'] * 1000, 2);
            }

            $cols[$timerLabel]['mem'] = Text::bytesIntToAbbr($time['mem'], 2, '.');
            $cols[$timerLabel]['%']   = number_format((($time['total'] * 100) / $timeTotal), 2);

            $cols[$timerLabel][$time['level']] = $percentOfParent;
            $cols[$timerLabel]['label']        = str_repeat($pad . $pad, max(0, $time['level'] - 2)) . ($time['rename'] ?? $timerLabel);

            $colLen['start']        = max(strlen('start'), $colLen['start'] ?? 0, strlen($cols[$timerLabel]['start'] ?? ''));
            $colLen['#']            = max(strlen('#'), $colLen['#'] ?? 0, strlen($cols[$timerLabel]['#'] ?? ''));
            $colLen['mem']          = max(strlen('mem'), $colLen['mem'] ?? 0, strlen($cols[$timerLabel]['mem'] ?? ''));
            $colLen['time']         = max(strlen('time'), $colLen['time'] ?? 0, strlen($cols[$timerLabel]['time'] ?? ''));
            $colLen['%']            = max(strlen('%'), $colLen['%'] ?? 0, strlen($cols[$timerLabel]['%'] ?? ''));
            $colLen[$time['level']] = max($colLen[$time['level']] ?? 0, strlen($cols[$timerLabel][$time['level']] ?? ''));
        }
        foreach ($cols as $time) {
            $colLen['label'] = max($colLen['label'] ?? 0, strlen($time['label'] ?? ''));
        }

        $r .= $prefix;
        foreach ($colLen as $col => $len) {
            if ($col == 'label') {
                $r .= str_pad($col, $len, ' ', STR_PAD_RIGHT) . ' ';
            } else {
                if (is_int($col)) {
                    if ($col < 2) continue;
                    $col -= 1;
                }
                $r .= str_pad($col ?? '', $len, $padHead, STR_PAD_LEFT) . ' ';
            }
        }
        $r .= $postfix;


        foreach ($cols as $val) {
            $r .= $prefix;
            foreach ($colLen as $col => $len) {
                if ($col == 'label') {
                    $r .= str_pad($val[$col] ?? '', $len, ' ', STR_PAD_RIGHT) . ' ';
                } else {
                    if (is_int($col) && $col < 2) continue;

                    $r .= str_pad($val[$col] ?? '', $len, $pad, STR_PAD_LEFT) . ' ';
                }
                // $len   += 1;
            }
            $r .= $postfix;
        }

        $memEnd      = ' ' . Text::bytesIntToAbbr(memory_get_usage(false), 2, $padHead);
        $memPeak     = ' ' . Text::bytesIntToAbbr(memory_get_peak_usage(false), 2, $padHead);
        $memEndReal  = ' ' . Text::bytesIntToAbbr(memory_get_usage(true), 2, $padHead);
        $memPeakReal = ' ' . Text::bytesIntToAbbr(memory_get_peak_usage(true), 2, $padHead);
        $incFiles    = ' ' . count(get_included_files());
        $memLenMax   = ' ' . max(strlen($memEnd), strlen($memPeak), strlen($memEndReal), strlen($memPeakReal), strlen($incFiles));

        if ($memory) {
            $r .= $prefix . 'mem at end ......' . str_pad($memEnd, $memLenMax, $padHead, STR_PAD_LEFT) . $postfix;
            $r .= $prefix . 'mem peak ........' . str_pad($memPeak, $memLenMax, $padHead, STR_PAD_LEFT) . $postfix;
            $r .= $prefix . 'mem real at end .' . str_pad($memEndReal, $memLenMax, $padHead, STR_PAD_LEFT) . $postfix;
            $r .= $prefix . 'mem real peak ...' . str_pad($memPeakReal, $memLenMax, $padHead, STR_PAD_LEFT) . ' / ' . ini_get('memory_limit') . $postfix;
        }
        $r .= $prefix . 'included files ..' . str_pad($incFiles, $memLenMax, $padHead, STR_PAD_LEFT) . $postfix;
        if ($includes) {
            $r         .= $prefix . ' ... ' . $postfix;
            $parentDir = dirname(G::$app->dir);
            foreach (get_included_files() as $file) {
                if (str_starts_with($file, G::$app->dir)) $file = './' . substr($file, strlen(G::$app->dir));
                else if (str_starts_with($file, $parentDir)) $file = '..' . substr($file, strlen($parentDir));
                $r .= $prefix . $file . $postfix;
            }
        }

        echo $r;
    }


    static function reset(): void {
        AppTimer::$timers      = [];
        AppTimer::$timerLevel  = 0;
        AppTimer::$timerMaxLen = 0;
        AppTimer::$timerMaxLev = 0;
        AppTimer::start('Total', microtime(true));
    }

}
