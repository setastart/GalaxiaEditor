<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Test;

use ReflectionException;

include_once dirname(__DIR__) . '/src/boot-cli-editor.php';


class GalaxiaTest {

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

    private function assertMessage(string $msg, ...$args): void {
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
        d($args);
    }

    function run(): void {
        $reflectorClass = new \ReflectionClass(get_called_class());

        foreach ($reflectorClass->getMethods() as $method) {
            try {
                $reflectorClass->getMethod("setUp")->invoke($this);
            } catch (reflectionException) {
            }

            if (str_starts_with($method->name, 'test')) {
                try {
                    $method->invoke($this);
                } catch (reflectionException) {
                }
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

}
