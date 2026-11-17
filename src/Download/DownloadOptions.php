<?php

namespace Plutuss\Download;

use InvalidArgumentException;

final readonly class DownloadOptions
{
    /** @param list<string> $allowedHosts Exact hostnames, without ports or wildcards. */
    public function __construct(
        public ?int $maxBytes = null,
        public ?float $timeout = null,
        public float $readTimeout = 30,
        public float $connectTimeout = 30,
        public int $maxRedirects = 20,
        public array $allowedHosts = [],
        public bool $allowPrivateNetworks = true,
    ) {
        if (($maxBytes !== null && $maxBytes < 0) || $maxRedirects < 0) {
            throw new InvalidArgumentException('Download size and redirect limits must be non-negative.');
        }
        foreach ([$timeout, $readTimeout, $connectTimeout] as $seconds) {
            if ($seconds !== null && (!is_finite($seconds) || $seconds <= 0)) {
                throw new InvalidArgumentException('Download timeouts must be positive finite seconds.');
            }
        }
        foreach ($allowedHosts as $host) {
            if (!is_string($host) || $host === '' || preg_match('/[\s\/\\\\@*]/', $host)) {
                throw new InvalidArgumentException('Allowed hosts must be exact non-empty hostnames or IP addresses.');
            }
        }
    }
}
