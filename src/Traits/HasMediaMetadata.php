<?php

namespace Plutuss\Traits;

use Plutuss\Exceptions\MediaAnalysisException;
use Plutuss\Metadata\AudioMetadata;
use Plutuss\Metadata\MetadataValue;
use Plutuss\Metadata\VideoMetadata;

trait HasMediaMetadata
{
    public function hasAudio(): bool
    {
        return is_array($this->getNestedValue('audio')) && $this->getNestedValue('audio') !== [];
    }

    public function hasVideo(): bool
    {
        return is_array($this->getNestedValue('video')) && $this->getNestedValue('video') !== [];
    }

    public function getDurationSeconds(): ?float
    {
        return MetadataValue::number($this->getNestedValue('playtime_seconds'));
    }

    public function getMimeType(): ?string
    {
        return MetadataValue::text($this->getNestedValue('mime_type'));
    }

    public function getAudioMetadata(): ?AudioMetadata
    {
        if (!$this->hasAudio()) {
            return null;
        }
        return new AudioMetadata(
            MetadataValue::text($this->getNestedValue('audio.codec', $this->getNestedValue('audio.dataformat'))),
            MetadataValue::number($this->getNestedValue('audio.bitrate')),
            MetadataValue::integer($this->getNestedValue('audio.sample_rate')),
            MetadataValue::integer($this->getNestedValue('audio.channels')),
            MetadataValue::integer($this->getNestedValue('audio.bits_per_sample')),
        );
    }

    public function getVideoMetadata(): ?VideoMetadata
    {
        if (!$this->hasVideo()) {
            return null;
        }
        return new VideoMetadata(
            MetadataValue::text($this->getNestedValue('video.codec', $this->getNestedValue('video.dataformat'))),
            MetadataValue::integer($this->getNestedValue('video.resolution_x')),
            MetadataValue::integer($this->getNestedValue('video.resolution_y')),
            MetadataValue::number($this->getNestedValue('video.frame_rate')),
            MetadataValue::number($this->getNestedValue('video.bitrate')),
            $this->rotation(),
        );
    }

    private function rotation(): ?float
    {
        $value = $this->getNestedValue('video.rotate', $this->getNestedValue('quicktime.video.rotate'));
        return is_numeric($value) && is_finite((float) $value) ? (float) $value : null;
    }

    public function getErrors(): array
    {
        return $this->messages('error');
    }

    public function getWarnings(): array
    {
        return $this->messages('warning');
    }

    private function messages(string $key): array
    {
        $value = $this->getNestedValue($key, []);
        return array_values(array_filter(is_array($value) ? $value : [$value], 'is_string'));
    }

    public function hasErrors(): bool
    {
        return $this->getErrors() !== [];
    }

    public function throwIfErrors(): static
    {
        if ($this->hasErrors()) {
            throw new MediaAnalysisException($this->getErrors());
        }
        return $this;
    }
}
