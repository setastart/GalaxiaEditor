<?php
/**
 * MIT License
 *
 * Copyright (c) 2016 Arminas Žukauskas - arminas@ini.lt
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

// Copyright 2017-2024 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia;

use Closure;

/**
 * Raw redis wrapper, all the commands are passed as-is
 * More information and usage examples could be found on https://github.com/ziogas/PHP-Redis-implementation
 *
 * Based on http://redis.io/topics/protocol
 */
class RedisCli {

    const string INTEGER   = ':';
    const string INLINE    = '+';
    const string BULK      = '$';
    const string MULTIBULK = '*';
    const string ERROR     = '-';
    const string NL        = "\r\n";

    public mixed    $handle;
    private Closure $errorFunction;
    private array   $cmds    = [];
    private string  $cmdLast = '';

    public function __construct(
        string $host = '127.0.0.1',
        int    $port = 6379,
        int    $timeoutStream = 60,
        int    $timeoutSocket = 3,
    ) {
        $this->handle = @fsockopen($host, $port, $errno, $errstr, $timeoutSocket);

        if (is_resource($this->handle)) stream_set_timeout($this->handle, $timeoutStream);
    }

    public function __destruct() {
        if (is_resource($this->handle)) fclose($this->handle);
    }

    // Push single command to queue
    public function cmd(...$args): static {
        if (!$this->handle) return $this;

        $cmd = '*' . count($args) . self::NL;
        foreach ($args as $arg) {
            $cmd .= '$' . strlen($arg) . self::NL . $arg . self::NL;
        }
        $this->cmds[] = $cmd;

        return $this;
    }

    // Push many commands at once, almost always for setting something
    public function set(): bool|array {
        if (!$this->handle) return false;

        $size     = $this->exec();
        $response = [];

        for ($i = 0; $i < $size; $i++) {
            $response[] = $this->getResponse();
        }

        return $response;
    }

    // Get command response
    public function get(): int|bool|array|string|null {
        if (!$this->handle) return false;
        if ($this->exec()) return $this->getResponse();

        return false;
    }

    // Get length of the returned array. Most useful with `Keys` command
    public function len(): bool|int|null {
        if (!$this->handle) return false;
        if (!$this->exec()) return null;

        return match (fgetc($this->handle)) {
            self::BULK      => count($this->bulkResponse()),
            self::MULTIBULK => count($this->multibulkResponse()),
            default         => null,
        };
    }

    // Parse single command single response
    private function getResponse(): array|bool|int|string|null {
        return match (fgetc($this->handle)) {
            self::INLINE    => $this->inlineResponse(),
            self::INTEGER   => $this->integerResponse(),
            self::BULK      => $this->bulkResponse(),
            self::MULTIBULK => $this->multibulkResponse(),
            self::ERROR     => $this->errorResponse(),
            default         => false,
        };
    }

    private function inlineResponse(): string {
        return trim(fgets($this->handle));
    }

    private function integerResponse(): int {
        return (int)trim(fgets($this->handle));
    }

    private function errorResponse(): bool {
        $error = fgets($this->handle);

        if (is_callable($this->errorFunction)) {
            call_user_func($this->errorFunction, "$error($this->cmdLast)");
        }

        return false;
    }

    private function bulkResponse(): mixed {
        $return = trim(fgets($this->handle));
        if ($return === '-1') return null;

        return $this->readBulkResponse($return);
    }

    private function multibulkResponse(): ?array {
        $size = trim(fgets($this->handle));
        if ($size === '-1') return null;

        $return = [];
        for ($i = 0; $i < $size; $i++) {
            $return[] = $this->getResponse();
        }

        return $return;
    }

    // Sends command to the redis
    private function exec(): ?int {
        $cmdCount = count($this->cmds);
        if ($cmdCount < 1) return null;

        if (isset($this->errorFunction)) {
            $this->cmdLast = str_replace(self::NL, '\\r\\n', implode(';', $this->cmds));
        }

        $command = implode(self::NL, $this->cmds) . self::NL;
        fwrite($this->handle, $command);

        $this->cmds = [];
        return $cmdCount;
    }

    // Bulk response reader
    private function readBulkResponse($tmp): ?string {
        $response = null;

        $read = 0;
        $size = strlen($tmp) > 1 && substr($tmp, 0, 1) === self::BULK ? substr($tmp, 1) : $tmp;

        while ($read < $size) {
            $diff = $size - $read;

            $block_size = min($diff, 8192);

            $chunk = fread($this->handle, $block_size);

            if ($chunk !== false) {
                $chunkLen = strlen($chunk);
                $read     += $chunkLen;
                $response .= $chunk;
            } else {
                fseek($this->handle, $read);
            }
        }

        fgets($this->handle);

        return $response;
    }

    public function setErrorFunction(Closure $f): void {
        $this->errorFunction = $f;
    }

}
