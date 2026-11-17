<?php

namespace Plutuss\Metadata;

/** Numeric rates use bits/second and samples/second (Hz). */
final readonly class AudioMetadata
{
    public function __construct(
        public ?string $codec,
        public ?float $bitrate,
        public ?int $sampleRate,
        public ?int $channels,
        public ?int $bitsPerSample,
    ) {}
}
