<?php

namespace Plutuss\Metadata;

/** Dimensions are encoded pixels; rotation is clockwise degrees when reported. */
final readonly class VideoMetadata
{
    public function __construct(
        public ?string $codec,
        public ?int $width,
        public ?int $height,
        public ?float $frameRate,
        public ?float $bitrate,
        public ?float $rotation,
    ) {}

    public function getDisplayWidth(): ?int
    {
        return $this->displayDimension($this->width, $this->height);
    }

    public function getDisplayHeight(): ?int
    {
        return $this->displayDimension($this->height, $this->width);
    }

    private function displayDimension(?int $normal, ?int $rotated): ?int
    {
        $angle = fmod(fmod($this->rotation ?? 0, 360) + 360, 360);
        if (abs($angle - 90) < 0.01 || abs($angle - 270) < 0.01) {
            return $rotated;
        }
        if (abs($angle) < 0.01 || abs($angle - 180) < 0.01) {
            return $normal;
        }

        // Arbitrary rotations require more than swapping pixel dimensions.
        return null;
    }
}
