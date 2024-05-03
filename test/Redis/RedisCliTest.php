<?php
// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

/**
 * Changes from the original:
 * - update to php 8.1
 * - remove dependency on phpunit
 * - reformat
 */

namespace Test\Redis;

use Galaxia\RedisCli;
use Test\GalaxiaTest;

include_once dirname(__DIR__) . "/GalaxiaTest.php";


class RedisCliTest extends GalaxiaTest {

    static RedisCli $redis;

    function setUp(): void {
        self::$redis = new RedisCli('127.0.0.1', 6379);
        self::$redis->setErrorFunction(function($error) { echo 'Error: ' . $error . PHP_EOL; });
    }

    function tearDown(): void {
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


    function testSimpleVariables(): void {
        self::$redis->cmd('SET', 'foo', 'bar')->set();
        $value = self::$redis->cmd('GET', 'foo')->get();

        self::assertEqual('bar', $value);
    }

    function testHset(): void {
        self::$redis->cmd('HSET', 'hash', 'foo', 'bar')
            ->cmd('HSET', 'hash', 'abc', 'def')
            ->cmd('HSET', 'hash', '123', '456')
            ->set();

        $vals         = self::$redis->cmd('HGETALL', 'hash')->get();
        $totalRecords = self::$redis->cmd('HVALS', 'hash')->len();

        self::assertEqual(['foo', 'bar', 'abc', 'def', '123', '456'], $vals);
        self::assertEqual(3, $totalRecords);
    }

    function testIncrements(): void {
        self::$redis->cmd('INCR', 'online_foo')
            ->cmd('INCR', 'online_bar')
            ->cmd('INCRBY', 'online_foo', 3)
            ->set();

        $totalOnlineUnique = (int)self::$redis->cmd('KEYS', 'online*')->len();
        $fooOnline         = (int)self::$redis->cmd('GET', 'online_foo')->get();

        self::assertEqual(2, $totalOnlineUnique);
        self::assertEqual(4, $fooOnline);
    }

    function testSets(): void {
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

        self::assertEqual(900, $expiration);
        self::assertEqual(3, $totalItems);
        self::assertEqual(['Even more data', 'More data', 'Some data'], $list);
        self::assertEqual([], $emptyList);
    }

    function testLrange(): void {
        self::$redis->cmd('RPUSH', 'range', 'one')
            ->cmd('RPUSH', 'range', 'two')
            ->cmd('RPUSH', 'range', 'three')
            ->set();

        self::assertEqual(['one'], self::$redis->cmd('LRANGE', 'range', '0', '0')->get());
        self::assertEqual(['one', 'two', 'three'], self::$redis->cmd('LRANGE', 'range', '-3', '2')->get());
        self::assertEqual(['one', 'two', 'three'], self::$redis->cmd('LRANGE', 'range', '-100', '100')->get());
        self::assertEqual([], self::$redis->cmd('LRANGE', 'range', '5', '10')->get());
    }

    function testTransactions(): void {

        self::$redis->cmd('MULTI')->set();
        $queued = self::$redis->cmd('SET', 'multi_foo', 'bar')->get();
        self::$redis->cmd('EXEC')->set();

        $value = self::$redis->cmd('GET', 'multi_foo')->get();

        self::assertEqual('QUEUED', $queued);
        self::assertEqual('bar', $value);
    }

    function testTransactionsSingleLine(): void {

        self::$redis->cmd('MULTI')->cmd('INCR', 'incr_foo')->cmd('EXPIRE', 'incr_foo', 100)->cmd('EXEC')->set();

        $value = self::$redis->cmd('GET', 'incr_foo')->get();

        self::assertEqual('1', $value);
    }

}


$sut = new RedisCliTest();
$sut->run();
