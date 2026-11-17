<?php

namespace Plutuss\Tests;

use Plutuss\DTO\MediaAnalyzerDTO;
use Plutuss\DTO\MediaMetadataInterface;
use Plutuss\Exceptions\MediaAnalysisException;

class MetadataTest extends PackageTestCase
{
    public function test_missing_metadata_has_explicit_nulls(): void
    {
        $media = new MediaAnalyzerDTO(null);
        self::assertInstanceOf(MediaMetadataInterface::class, $media);
        self::assertFalse($media->hasAudio());
        self::assertFalse($media->hasVideo());
        self::assertNull($media->getDurationSeconds());
        self::assertNull($media->getMimeType());
        self::assertNull($media->getAudioMetadata());
        self::assertNull($media->getVideoMetadata());
        self::assertSame('', $media->getPlaytimeSeconds());
        self::assertSame($media, $media->throwIfErrors());
    }

    public function test_numeric_metadata_is_normalized_with_explicit_units(): void
    {
        $media = new MediaAnalyzerDTO(collect([
            'mime_type' => 'video/mp4', 'playtime_seconds' => '1.25',
            'audio' => ['dataformat' => 'aac', 'bitrate' => '128000', 'sample_rate' => '48000', 'channels' => 2],
            'video' => ['codec' => 'H.264', 'resolution_x' => 1920.0, 'resolution_y' => '1080', 'frame_rate' => '29.97', 'bitrate' => '2500000'],
        ]));
        self::assertTrue($media->hasAudio());
        self::assertTrue($media->hasVideo());
        self::assertSame(1.25, $media->getDurationSeconds());
        self::assertSame('video/mp4', $media->getMimeType());
        self::assertSame('aac', $media->getAudioMetadata()->codec);
        self::assertSame(48000, $media->getAudioMetadata()->sampleRate);
        self::assertSame(128000.0, $media->getAudioMetadata()->bitrate);
        self::assertSame(2, $media->getAudioMetadata()->channels);
        self::assertNull($media->getAudioMetadata()->bitsPerSample);
        self::assertSame(1920, $media->getVideoMetadata()->width);
        self::assertSame(29.97, $media->getVideoMetadata()->frameRate);
        self::assertNull($media->getVideoMetadata()->rotation);
    }

    public function test_display_dimensions_handle_rotation_and_keep_encoded_dimensions(): void
    {
        foreach ([90, 270, -90, -450, 450] as $angle) {
            $video = (new MediaAnalyzerDTO(collect(['video' => [
                'resolution_x' => 1920, 'resolution_y' => 1080, 'rotate' => $angle,
            ]])))->getVideoMetadata();
            self::assertSame(1920, $video->width);
            self::assertSame(1080, $video->getDisplayWidth());
            self::assertSame(1920, $video->getDisplayHeight());
        }
        $video = (new MediaAnalyzerDTO(collect(['video' => [
            'resolution_x' => 1920, 'resolution_y' => 1080, 'rotate' => 45,
        ]])))->getVideoMetadata();
        self::assertNull($video->getDisplayWidth());
    }

    public function test_invalid_numeric_values_do_not_become_zero(): void
    {
        foreach (['unknown', -1, NAN, INF, false, []] as $value) {
            $media = new MediaAnalyzerDTO(collect(['playtime_seconds' => $value, 'audio' => ['channels' => $value]]));
            self::assertNull($media->getDurationSeconds());
            self::assertNull($media->getAudioMetadata()->channels);
        }
        self::assertSame(0.0, (new MediaAnalyzerDTO(collect(['playtime_seconds' => 0])))->getDurationSeconds());
    }

    public function test_warnings_are_independent_from_errors(): void
    {
        $media = new MediaAnalyzerDTO(collect(['warning' => ['Recoverable warning']]));
        self::assertSame(['Recoverable warning'], $media->getWarnings());
        self::assertFalse($media->hasErrors());
        self::assertSame($media, $media->throwIfErrors());
    }

    public function test_analysis_exception_preserves_all_errors(): void
    {
        $media = new MediaAnalyzerDTO(collect(['error' => ['First error', 'Second error']]));
        self::assertTrue($media->hasErrors());
        try {
            $media->throwIfErrors();
            self::fail('Expected analysis exception.');
        } catch (MediaAnalysisException $exception) {
            self::assertSame(['First error', 'Second error'], $exception->errors);
            self::assertStringContainsString('Second error', $exception->getMessage());
        }
        self::assertSame(['Single error'], (new MediaAnalyzerDTO(collect(['error' => 'Single error'])))->getErrors());
    }
}
