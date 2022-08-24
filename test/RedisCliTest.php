<?php
/*
 Copyright 2017-2022 Ino Detelić & Zaloa G. Ramos

  - Licensed under the EUPL, Version 1.2 only (the "Licence");
  - You may not use this work except in compliance with the Licence.

  - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

  - Unless required by applicable law or agreed to in writing, software distributed
    under the Licence is distributed on an "AS IS" basis,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  - See the Licence for the specific language governing permissions and limitations under the Licence.
 */

/**
 * Changes from the original:
 * - update to php 8.1
 * - remove dependency on phpunit
 * - reformat
 */

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'RedisCli.php';


class RedisCliTest {

    static RedisCli $redis;
    static array    $tests;
    static int      $ok    = 0;
    static int      $total = 0;

    static function setUp(): void {
        self::$redis = new RedisCli('127.0.0.1', 6379);
        self::$redis->setErrorFunction(function($error) { echo 'Error: ' . $error . PHP_EOL; });
    }

    static function testSimpleVariables(): void {
        self::$redis->cmd('SET', 'foo', 'bar')->set();
        $value = self::$redis->cmd('GET', 'foo')->get();

        self::assertSame(__METHOD__, __LINE__, 'bar', $value);
    }

    static function testHset(): void {
        self::$redis->cmd('HSET', 'hash', 'foo', 'bar')
            ->cmd('HSET', 'hash', 'abc', 'def')
            ->cmd('HSET', 'hash', '123', '456')
            ->set();

        $vals         = self::$redis->cmd('HGETALL', 'hash')->get();
        $totalRecords = self::$redis->cmd('HVALS', 'hash')->len();

        self::assertSame(__METHOD__, __LINE__, ['foo', 'bar', 'abc', 'def', '123', '456'], $vals);
        self::assertSame(__METHOD__, __LINE__, 3, $totalRecords);
    }

    static function testIncrements(): void {
        self::$redis->cmd('INCR', 'online_foo')
            ->cmd('INCR', 'online_bar')
            ->cmd('INCRBY', 'online_foo', 3)
            ->set();

        $totalOnlineUnique = (int)self::$redis->cmd('KEYS', 'online*')->len();
        $fooOnline         = (int)self::$redis->cmd('GET', 'online_foo')->get();

        self::assertSame(__METHOD__, __LINE__, 2, $totalOnlineUnique);
        self::assertSame(__METHOD__, __LINE__, 4, $fooOnline);
    }

    static function testSets(): void {
        $hash = 'set_structure';
        self::$redis->cmd('SADD', $hash, 'Some data')->set();
        self::$redis->cmd('SADD', $hash, 'More data')->set();
        self::$redis->cmd('SADD', $hash, 'Even more data')->set();

        self::$redis->cmd('EXPIRE', $hash, 900)->set();

        $expiration = self::$redis->cmd('TTL', $hash)->get();
        $totalItems = self::$redis->cmd('SCARD', $hash)->get();
        $list       = self::$redis->cmd('SMEMBERS', $hash)->get();
        sort($list);

        self::$redis->cmd('DEL', $hash)->set();
        $emptyList = self::$redis->cmd('SMEMBERS', $hash)->get();

        self::assertSame(__METHOD__, __LINE__, 900, $expiration);
        self::assertSame(__METHOD__, __LINE__, 3, $totalItems);
        self::assertSame(__METHOD__, __LINE__, ['Even more data', 'More data', 'Some data'], $list);
        self::assertSame(__METHOD__, __LINE__, [], $emptyList);
    }

    static function testLrange(): void {
        self::$redis->cmd('RPUSH', 'range', 'one')
            ->cmd('RPUSH', 'range', 'two')
            ->cmd('RPUSH', 'range', 'three')
            ->set();

        self::assertSame(__METHOD__, __LINE__, ['one'], self::$redis->cmd('LRANGE', 'range', '0', '0')->get());
        self::assertSame(__METHOD__, __LINE__, ['one', 'two', 'three'], self::$redis->cmd('LRANGE', 'range', '-3', '2')->get());
        self::assertSame(__METHOD__, __LINE__, ['one', 'two', 'three'], self::$redis->cmd('LRANGE', 'range', '-100', '100')->get());
        self::assertSame(__METHOD__, __LINE__, [], self::$redis->cmd('LRANGE', 'range', '5', '10')->get());
    }

    static function testTransactions(): void {

        self::$redis->cmd('MULTI')->set();
        $queued = self::$redis->cmd('SET', 'multi_foo', 'bar')->get();
        self::$redis->cmd('EXEC')->set();

        $value = self::$redis->cmd('GET', 'multi_foo')->get();

        self::assertSame(__METHOD__, __LINE__, 'QUEUED', $queued);
        self::assertSame(__METHOD__, __LINE__, 'bar', $value);
    }

    static function testTransactionsSingleLine(): void {

        self::$redis->cmd('MULTI')->cmd('INCR', 'incr_foo')->cmd('EXPIRE', 'incr_foo', 100)->cmd('EXEC')->set();

        $value = self::$redis->cmd('GET', 'incr_foo')->get();

        self::assertSame(__METHOD__, __LINE__, '1', $value);
    }

    static function tearDown(): void {
        $keys = [
            'foo',
            'online_foo',
            'online_bar',
            'hash',
            'set_structure',
            'range',
            'multi_foo',
            'incr_foo',
        ];

        foreach ($keys as $key) {
            self::$redis->cmd('DEL', $key)->set();
        }
    }

    static function assertSame($method, $line, $a, $b): void {
        self::$tests["$method:$line"] = $a == $b;
        if ($a != $b) {
            echo "fail: $method:$line - $a != $b";
        } else {
            self::$ok++;
        }
        self::$total++;
    }

}

RedisCliTest::setUp();
RedisCliTest::testSimpleVariables();
RedisCliTest::testHset();
RedisCliTest::testIncrements();
RedisCliTest::testSets();
RedisCliTest::testLrange();
RedisCliTest::testTransactions();
RedisCliTest::testTransactionsSingleLine();
RedisCliTest::tearDown();

echo RedisCliTest::$ok . ' of '. RedisCliTest::$total . " tests passed" . PHP_EOL;
