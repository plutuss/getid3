<?php

namespace Plutuss\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Plutuss\Rules\MediaRule;
use Plutuss\Services\ConfigurableMediaAnalyzerInterface;

class MediaFormatsTest extends PackageTestCase
{
    private function fixture(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, file_get_contents(__DIR__ . '/fixtures/' . $name));
    }

    public function test_real_mp3_and_flac_metadata(): void
    {
        foreach (['tone.mp3', 'tone.flac'] as $name) {
            $media = app(ConfigurableMediaAnalyzerInterface::class)->uploadFile($this->fixture($name))->throwIfErrors();
            self::assertTrue($media->hasAudio());
            self::assertFalse($media->hasVideo());
            self::assertSame(44100, $media->getAudioMetadata()->sampleRate);
            self::assertGreaterThan(0.2, $media->getDurationSeconds());
            self::assertLessThan(0.6, $media->getDurationSeconds());
            self::assertSame('Synthetic-tone', $media->getTitle());
        }
    }

    public function test_real_mp4_has_typed_video_and_audio_metadata(): void
    {
        $media = app(ConfigurableMediaAnalyzerInterface::class)->uploadFile($this->fixture('clip.mp4'))->throwIfErrors();
        self::assertTrue($media->hasAudio());
        self::assertTrue($media->hasVideo());
        self::assertSame(64, $media->getVideoMetadata()->width);
        self::assertSame(48, $media->getVideoMetadata()->height);
        self::assertEqualsWithDelta(10, $media->getVideoMetadata()->frameRate, 0.01);
        self::assertGreaterThan(0, $media->getDurationSeconds());
    }

    public function test_real_mov_rotation_is_used_by_validation(): void
    {
        $file = $this->fixture('rotated.mov');
        $media = app(ConfigurableMediaAnalyzerInterface::class)->uploadFile($file)->throwIfErrors();
        self::assertSame(64, $media->getVideoMetadata()->width);
        self::assertSame(48, $media->getVideoMetadata()->getDisplayWidth());
        self::assertSame(64, $media->getVideoMetadata()->getDisplayHeight());
        $validator = Validator::make(['media' => $file], ['media' => [
            MediaRule::video()->minResolution(48, 64)->maxResolution(48, 64)->maxDuration(1)->requireAudio(),
        ]]);
        self::assertTrue($validator->passes(), implode(' ', $validator->errors()->all()));
    }

    public function test_real_png_passes_image_rules_but_fails_video_rules(): void
    {
        $file = $this->fixture('pixel.png');
        self::assertFalse(app(ConfigurableMediaAnalyzerInterface::class)->uploadFile($file)->hasVideo());
        $validator = Validator::make(['media' => $file], ['media' => [MediaRule::image()->minResolution(16, 12)->maxResolution(16, 12)]]);
        self::assertTrue($validator->passes(), implode(' ', $validator->errors()->all()));
        self::assertTrue(Validator::make(['media' => $file], ['media' => [MediaRule::video()]])->fails());
    }

    public function test_truncated_media_exposes_errors_and_fails_validation(): void
    {
        $file = UploadedFile::fake()->createWithContent('truncated.mp4', substr(file_get_contents(__DIR__ . '/fixtures/clip.mp4'), 0, 24));
        $media = app(ConfigurableMediaAnalyzerInterface::class)->uploadFile($file);
        self::assertTrue($media->hasErrors());
        self::assertTrue(Validator::make(['media' => $file], ['media' => [MediaRule::video()]])->fails());
    }
}
