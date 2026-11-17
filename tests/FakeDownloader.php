<?php

namespace Plutuss\Tests;

use Plutuss\Download\Downloader;

class FakeDownloader extends Downloader
{
    public array $responses = [];
    public array $requests = [];
    public array $streams = [];
    public int $delayMicroseconds = 0;

    protected function open(string $url, array $options): array
    {
        $this->requests[] = [$url, $options];
        if ($this->delayMicroseconds > 0) {
            usleep($this->delayMicroseconds);
        }
        [$body, $headers] = array_shift($this->responses);
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $body);
        rewind($stream);
        $this->streams[] = $stream;
        return [$stream, $headers];
    }
}
