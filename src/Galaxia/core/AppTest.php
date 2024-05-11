<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;

use GalaxiaEditor\config\Config;
use GalaxiaEditor\config\ConfigDb;
use GalaxiaEditor\E;
use ReflectionException;
use function str_replace;

class AppTest {

    public int $ok    = 0;
    public int $total = 0;

    function error($msg): never {
        db();
        dd("❌ Error", $msg);
    }

    function setUp(): void { }

    function tearDown(): void { }

    function assertEqual(mixed $a, mixed $b, string $msg = ""): void {
        if ($a === $b) {
            $this->ok++;
        } else {
            $this->assertMessage($msg, $a, $b);
        }
        $this->total++;
    }

    function assertNotEqual(mixed $a, mixed $b, string $msg = ""): void {
        if ($a !== $b) {
            $this->ok++;
        } else {
            $this->assertMessage($msg, $a, $b);
        }
        $this->total++;
    }

    function assertTrue(mixed $a, string $msg = ""): void {
        $this->assertEqual($a, true, $msg);
    }

    function assertFalse(mixed $a, string $msg = ""): void {
        $this->assertEqual($a, false, $msg);
    }

    function assertNull(mixed $a, string $msg = ""): void {
        $this->assertEqual($a, null, $msg);
    }

    function assertNotNull(mixed $a, string $msg = ""): void {
        $this->assertNotEqual($a, null, $msg);
    }

    function assertEmpty(mixed $a, string $msg = ""): void {
        $this->assertEqual(empty($a), true, $msg);
    }

    function assertNotEmpty(mixed $a, string $msg = ""): void {
        $this->assertEqual(empty($a), false, $msg);
    }

    private function assertMessage(string $msg, mixed $result, mixed $expect): void {
        echo "❌ Assert fail: $msg" . PHP_EOL;

        $e = new \Exception();
        foreach ($e->getTrace() as $frame) {
            if (($frame["file"] ?? '') == __FILE__) {
                continue;
            }
            echo sprintf(
                "   %s:%d - %s%s%s()\n",
                $frame["file"] ?? '',
                $frame["line"] ?? '',
                $frame["class"] ?? '',
                $frame["type"] ?? '',
                $frame["function"] ?? ''
            );
        }
        d(['result' => $result, 'expect' => $expect]);
    }

    function run(): void {
        $reflectorClass = new \ReflectionClass(get_called_class());

        foreach ($reflectorClass->getMethods() as $method) {
            if (!str_starts_with($method->name, 'test')) {
                continue;
            }

            try {
                $reflectorClass->getMethod("setUp")->invoke($this);
            } catch (reflectionException) {
            }

            try {
                $method->invoke($this);
            } catch (reflectionException) {
            }

            try {
                $reflectorClass->getMethod("tearDown")->invoke($this);
            } catch (reflectionException) {
            }
        }

        $this->finish();
    }

    private function finish(bool $exitOnFail = true): void {
        $className = get_called_class();
        if ($this->total == 0) {
            echo "⚠️️ $className didn't run. 0 tests." . PHP_EOL;
        } else if ($this->ok === $this->total) {
            echo "✅ $className passed all $this->total tests." . PHP_EOL;
        } else {
            $failedCount = $this->total - $this->ok;
            echo "❌❌❌ $className failed $failedCount tests. $this->ok / $this->total." . PHP_EOL;
            if ($exitOnFail) {
                exit(1);
            }
        }
    }

    static function urls(
        array    $tests,
        string   $host,
        int      $argc,
        bool     $exitOnError = false,
        int      $simultaneous = 7,
    ): void {
        global $argv;

        $isBench = false;
        foreach ($argv as $argn => $val) {
            if ($val == '--bench') {
                unset($argv[$argn]);
                $argc--;
                $isBench = true;
            }
        }

        if ($argc > 2 || $argc < 1) {
            echo 'Usage:' . PHP_EOL;
            echo 'run tests: php test.php [--bench]' . PHP_EOL;
            echo 'test single page: php test.php http://example.test/url' . PHP_EOL;
            echo '  --bench runs performance benchmark after test' . PHP_EOL;
            exit();
        }

        if ($argc == 2) {
            $tests = [$argv[1] => '200'];
        }

        $testsPassed = 0;
        $testsTotal  = count($tests);

        echo PHP_EOL . 'Testing ' . $host . " ($testsTotal) simultaneous: $simultaneous" . PHP_EOL;

        G::initEditor();
        echo 'Validating config for perms: ';
        foreach ([[], ['dev']] as $perms) {
            echo "'" . implode(',', $perms) . "' ";
            E::$conf = Config::load($perms);
            ConfigDb::validate();
        }
        echo PHP_EOL;

        $mTests = [];
        $i      = 0;
        foreach ($tests as $url => $code) {
            $mTests[floor($i / $simultaneous)][$url] = $code;
            $i++;
        }

        $testDir = dirname(G::$app->dir) . '/tests/' . $host;
        if (!is_dir($testDir)) mkdir($testDir);

        $timeTotal = 0.0;
        $timeStart = microtime(true);
        $timeMin   = 1000.0;
        $timeMax   = 0.0;
        $i         = 0;
        foreach ($mTests as $urls) {
            $ch  = [];
            $res = [];
            $j   = 0;
            foreach ($urls as $url => $code) {
                $ch[$j] = curl_init();
                curl_setopt($ch[$j], CURLOPT_URL, $url);
                curl_setopt($ch[$j], CURLOPT_HEADER, 0);
                curl_setopt($ch[$j], CURLOPT_HTTPHEADER, ['X-GalaxiaEditor-Test: 1']);
                curl_setopt($ch[$j], CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch[$j], CURLOPT_FRESH_CONNECT, true);
                $j++;
            }

            $mh = curl_multi_init();

            $j = 0;
            foreach ($urls as $ignored) {
                curl_multi_add_handle($mh, $ch[$j]);
                $j++;
            }

            //execute the multi handle
            do {
                $status = curl_multi_exec($mh, $active);
                if ($active) {
                    curl_multi_select($mh);
                }
                while (false !== ($mhInfo = curl_multi_info_read($mh))) {
                    if ($i == 0) {
                        echo '0...';
                    } else {
                        echo ($i % 100 == 0) ? $i . ',' : '';
                    }
                    $i++;
                    if ($i == $testsTotal) echo $i . '!';

                    $info      = curl_getinfo($mhInfo['handle']);
                    $timeTotal += $info['total_time'];
                    $timeMin   = min($timeMin, $info['total_time']);
                    $timeMax   = max($timeMax, $info['total_time']);

                    $res[$info['url']] = $info['http_code'] . ' - ' . parse_url($info['redirect_url'], PHP_URL_PATH);
                }
            } while ($active && $status == CURLM_OK);

            $j = 0;
            foreach ($urls as $url => $code) {
                $res[$url] .= curl_multi_getcontent($ch[$j]);
                curl_multi_remove_handle($mh, $ch[$j]);
                $j++;
            }
            curl_multi_close($mh);

            foreach ($urls as $url => $code) {
                if (G::isDevEnv()) {
                    $slug = parse_url($url, PHP_URL_PATH);
                    $slug = str_replace('/', '_', $slug);
                    $slug = preg_replace('~[^a-z0-9-_]+~u', '-', $slug);
                    $slug = ltrim($slug, '_');

                    file_put_contents(
                        filename: "{$testDir}/{$slug}.html",
                        data: $res[$url]
                    );
                }

                if (str_starts_with($res[$url], $code)) {
                    $testsPassed++;
                    continue;
                }

                $res[$url] = escapeshellcmd($res[$url]);
                // $res[$url] = substr($res[$url], 0, 80);

                echo PHP_EOL . 'Error: ' . $url . " -- expected: $code -- returned: $res[$url]";

                if ($exitOnError) {
                    break 2;
                }
            }
        }
        $timeEnd = microtime(true);
        $time = ($timeEnd - $timeStart);

        $reqS = number_format($testsTotal / $time, 2);
        $avg   = number_format(($timeTotal / $i) * 1000, 2);
        $color = "\033[0;32m";
        if ($avg > 10) $color = "\033[0;33m";
        if ($avg > 30) $color = "\033[0;31m";
        $avg = "$color$avg\e[0m";

        $min = number_format($timeMin * 1000, 2);
        $max = number_format($timeMax * 1000, 2);

        $prefix = ($testsPassed == count($tests)) ? '[OK] ✅ ' : '[FAIL] ❌ ';

        echo PHP_EOL . "$prefix $testsPassed/$testsTotal tests passed. req/s: $reqS, avg: $avg, min: $min, max: $max" . PHP_EOL;

        if ($isBench) AppTest::bench(tests: $tests, host: $host, argc: $argc, exitOnError: $exitOnError);

        if ($testsPassed != count($tests)) {
            exit(1);
        };
    }


    static function bench(
        array  $tests,
        string $host,
        int    $argc,
        bool   $exitOnError = false,
    ): void {
        if ($argc > 2 || $argc < 1) {
            echo 'Usage:' . PHP_EOL;
            echo 'run tests: php test.php' . PHP_EOL;
            echo 'test single page: php test.php http://example.com/url' . PHP_EOL;
            exit();
        }

        if ($argc == 2) {
            global $argv;
            $tests = [$argv[1] => '200'];
        }

        $benchs      = [];
        $testsPassed = 0;
        $testsTotal  = count($tests);

        echo PHP_EOL . 'Benchmarking ' . $host . " ($testsTotal)" . PHP_EOL;

        $timeTotal = 0.0;
        $timeMin   = 1000.0;
        $timeMax   = 0.0;
        $i         = 0;
        foreach ($tests as $url => $code) {
            if ($i == 0) {
                echo '0...';
            } else {
                echo ($i % 100 == 0) ? $i . ',' : '';
            }
            $i++;
            if ($i == $testsTotal) echo $i . '!';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);

            $info   = curl_getinfo($ch);
            $result = $info['http_code'] . ' - ' . parse_url($info['redirect_url'], PHP_URL_PATH) . $result;


            if (str_starts_with($result, $code)) {
                $testsPassed++;
                $benchs[$info['http_code'] . ' - ' . $url] = $info['total_time'] * 1000;

                $timeTotal += $info['total_time'];
                $timeMin   = min($timeMin, $info['total_time']);
                $timeMax   = max($timeMax, $info['total_time']);
                continue;
            }

            $result = escapeshellcmd($result);
            // $result = substr($result, 0, 80);

            echo PHP_EOL . 'Error: ' . $url . " -- expected: $code -- returned: $result";

            if ($exitOnError) {
                break;
            }
        }

        echo PHP_EOL;
        asort($benchs);
        $benchs = array_slice($benchs, -10, preserve_keys: true);
        foreach ($benchs as $url => $time) {
            $color = "\033[0;32m";
            if ($time > 10) $color = "\033[0;33m";
            if ($time > 30) $color = "\033[0;31m";

            $timeColored = str_pad("$color$time\e[0m", 18, ' ', STR_PAD_LEFT);

            echo "$timeColored - $url" . PHP_EOL;
        }

        $reqS  = number_format($testsTotal / $time, 2);
        $avg   = number_format(($timeTotal / $i) * 1000, 2);
        $color = "\033[0;32m";
        if ($avg > 10) $color = "\033[0;33m";
        if ($avg > 30) $color = "\033[0;31m";
        $avg = "$color$avg\e[0m";

        $min = number_format($timeMin * 1000, 2);
        $max = number_format($timeMax * 1000, 2);

        $prefix = ($testsPassed == count($tests)) ? '[OK] ✅ ' : '[FAIL] ❌ ';

        echo PHP_EOL . "$prefix $testsPassed/$testsTotal benchmarks passed. req/s: $reqS, avg: $avg, min: $min, max: $max" . PHP_EOL;
    }

}
