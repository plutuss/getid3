<?php

namespace Plutuss\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Plutuss\DTO\MediaAnalyzerDTO;
use Plutuss\DTO\MediaMetadataInterface;
use Plutuss\Exceptions\GetID3Exception;
use Plutuss\Metadata\MetadataValue;
use Plutuss\Services\MediaAnalyzerServiceInterface;

final class MediaRule implements ValidationRule
{
    private ?float $maximumDuration = null;
    private ?float $minimumDuration = null;
    private ?array $minimumResolution = null;
    private ?array $maximumResolution = null;
    private bool $audioRequired = false;
    private array $mimes = [];

    private function __construct(private string $type) {}

    public static function video(): self { return new self('video'); }
    public static function audio(): self { return new self('audio'); }
    public static function image(): self { return new self('image'); }
    public static function media(): self { return new self('media'); }

    public function maxDuration(float $seconds): self
    {
        $this->checkDuration($seconds);
        $this->maximumDuration = $seconds;
        return $this;
    }

    public function minDuration(float $seconds): self
    {
        $this->checkDuration($seconds);
        $this->minimumDuration = $seconds;
        return $this;
    }

    public function minResolution(int $width, int $height): self
    {
        $this->checkResolution($width, $height);
        $this->minimumResolution = [$width, $height];
        return $this;
    }

    public function maxResolution(int $width, int $height): self
    {
        $this->checkResolution($width, $height);
        $this->maximumResolution = [$width, $height];
        return $this;
    }

    public function requireAudio(bool $required = true): self
    {
        $this->audioRequired = $required;
        return $this;
    }

    /** @param list<string> $mimes */
    public function mimeTypes(array $mimes): self
    {
        foreach ($mimes as $mime) {
            if (!is_string($mime) || !preg_match('~^[a-z0-9!#$&^_.+-]+/[a-z0-9!#$&^_.+-]+$~iD', $mime)) {
                throw new InvalidArgumentException('MIME types must be exact type/subtype strings.');
            }
        }
        $this->mimes = array_map('strtolower', $mimes);
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $reject = static function (string $key, array $parameters = []) use ($fail): void {
            $fail('getid3::validation.' . $key)->translate($parameters);
        };
        if (!$value instanceof UploadedFile || !$value->isValid()) {
            $reject('file');
            return;
        }
        try {
            // Every constraint below shares this single analysis result.
            $result = app(MediaAnalyzerServiceInterface::class)->uploadFile($value);
            $media = $result instanceof MediaMetadataInterface ? $result : new MediaAnalyzerDTO($result->getAllInfo());
        } catch (GetID3Exception $exception) {
            $reject('invalid');
            return;
        }
        if ($media->hasErrors()) {
            $reject('invalid');
            return;
        }
        $mime = strtolower($media->getMimeType() ?? '');
        $image = str_starts_with($mime, 'image/');
        $matches = match ($this->type) {
            'video' => $media->hasVideo() && !$image,
            'audio' => $media->hasAudio() && (!$media->hasVideo() || str_starts_with($mime, 'audio/')),
            'image' => $image,
            default => $media->hasAudio() || $media->hasVideo() || $image,
        };
        if (!$matches) {
            $reject('type', ['type' => $this->type]);
            return;
        }
        if ($this->mimes !== [] && !in_array($mime, $this->mimes, true)) {
            $reject('mime', ['types' => implode(', ', $this->mimes)]);
        }
        if ($this->audioRequired && !$media->hasAudio()) {
            $reject('audio');
        }
        if ($this->maximumDuration !== null || $this->minimumDuration !== null) {
            $duration = $media->getDurationSeconds();
            if ($duration === null) {
                $reject('metadata', ['field' => 'duration']);
            } else {
                if ($this->maximumDuration !== null && $duration > $this->maximumDuration) {
                    $reject('duration_max', ['max' => $this->maximumDuration]);
                }
                if ($this->minimumDuration !== null && $duration < $this->minimumDuration) {
                    $reject('duration_min', ['min' => $this->minimumDuration]);
                }
            }
        }
        if ($this->minimumResolution !== null || $this->maximumResolution !== null) {
            $video = $media->getVideoMetadata();
            $width = $image ? MetadataValue::integer($media->getNestedValue('video.resolution_x')) : $video?->getDisplayWidth();
            $height = $image ? MetadataValue::integer($media->getNestedValue('video.resolution_y')) : $video?->getDisplayHeight();
            if ($width === null || $height === null) {
                $reject('metadata', ['field' => 'resolution']);
                return;
            }
            foreach (['min' => $this->minimumResolution, 'max' => $this->maximumResolution] as $bound => $size) {
                if ($size !== null && ($bound === 'min'
                    ? ($width < $size[0] || $height < $size[1])
                    : ($width > $size[0] || $height > $size[1]))) {
                    $reject('resolution_' . $bound, ['width' => $size[0], 'height' => $size[1]]);
                }
            }
        }
    }

    private function checkDuration(float $seconds): void
    {
        if (!is_finite($seconds) || $seconds < 0) {
            throw new InvalidArgumentException('Duration limits must be finite non-negative seconds.');
        }
    }

    private function checkResolution(int $width, int $height): void
    {
        if ($width <= 0 || $height <= 0) {
            throw new InvalidArgumentException('Resolution limits must use positive pixel dimensions.');
        }
    }
}
