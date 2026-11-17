<?php

namespace Plutuss\Tests;

/** In-memory HTTP transport for deterministic download tests. */
class HttpMediaStream
{
    public $context;
    public static string $contents = '';
    private int $offset = 0;

    public function stream_open($path, $mode, $options, &$openedPath): bool
    {
        return true;
    }

    public function stream_read($count): string
    {
        $chunk = substr(self::$contents, $this->offset, $count);
        $this->offset += strlen($chunk);
        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->offset >= strlen(self::$contents);
    }

    public function stream_stat(): array
    {
        return [];
    }
}
