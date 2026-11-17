<?php

namespace Plutuss\Tests;

use Orchestra\Testbench\TestCase;
use Plutuss\Providers\GetId3ServiceProvider;

abstract class PackageTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [GetId3ServiceProvider::class];
    }

    protected function wav(int $seconds = 1): string
    {
        $data = str_repeat("\x80", 8000 * $seconds);
        return 'RIFF' . pack('V', 36 + strlen($data)) . 'WAVEfmt '
            . pack('VvvVVvv', 16, 1, 1, 8000, 8000, 1, 8)
            . 'data' . pack('V', strlen($data)) . $data;
    }
}
