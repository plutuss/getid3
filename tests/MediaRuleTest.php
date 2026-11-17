<?php

namespace Plutuss\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Plutuss\DTO\MediaAnalyzerDTO;
use Plutuss\Rules\MediaRule;
use Plutuss\Services\MediaAnalyzerServiceInterface;

class MediaRuleTest extends PackageTestCase
{
    private function stubAnalysis(array $data): void
    {
        $analyzer = \Mockery::mock(MediaAnalyzerServiceInterface::class);
        $analyzer->shouldReceive('uploadFile')->once()->andReturn(new MediaAnalyzerDTO(collect($data)));
        $this->app->instance(MediaAnalyzerServiceInterface::class, $analyzer);
    }

    public function test_real_audio_upload_passes_multiple_constraints(): void
    {
        $file = UploadedFile::fake()->createWithContent('tone.wav', $this->wav());
        $validator = Validator::make(['media' => $file], ['media' => [
            MediaRule::audio()->minDuration(1)->maxDuration(1)->mimeTypes(['audio/wav'])->requireAudio(),
        ]]);
        self::assertTrue($validator->passes(), implode(' ', $validator->errors()->all()));
    }

    public function test_one_analysis_serves_every_constraint_and_uses_display_dimensions(): void
    {
        $this->stubAnalysis([
            'mime_type' => 'video/mp4', 'playtime_seconds' => 120,
            'audio' => ['channels' => 2],
            'video' => ['resolution_x' => 1920, 'resolution_y' => 1080, 'rotate' => 90],
        ]);
        $validator = Validator::make(['media' => UploadedFile::fake()->create('clip.mp4')], ['media' => [
            MediaRule::video()->maxDuration(120)->minDuration(1)->requireAudio()
                ->minResolution(1080, 1920)->maxResolution(1080, 1920)->mimeTypes(['VIDEO/MP4']),
        ]]);
        self::assertTrue($validator->passes());
    }

    public function test_spoofed_extension_does_not_turn_audio_into_video(): void
    {
        $file = UploadedFile::fake()->createWithContent('fake.mp4', $this->wav());
        self::assertTrue(Validator::make(['media' => $file], ['media' => [MediaRule::video()]])->fails());
    }

    public function test_violations_produce_translated_messages(): void
    {
        $this->stubAnalysis(['video' => ['resolution_x' => 640, 'resolution_y' => 360], 'mime_type' => 'video/mp4', 'playtime_seconds' => 121]);
        $validator = Validator::make(['media' => UploadedFile::fake()->create('clip.mp4')], ['media' => [
            MediaRule::video()->maxDuration(120)->minResolution(1280, 720)->requireAudio()->mimeTypes(['video/webm']),
        ]]);
        self::assertTrue($validator->fails());
        $messages = implode(' ', $validator->errors()->all());
        self::assertStringContainsString('120 seconds', $messages);
        self::assertStringContainsString('1280', $messages);
        self::assertStringContainsString('audio track', $messages);
        self::assertStringNotContainsString('getid3::', $messages);
    }

    public function test_missing_duration_fails_instead_of_passing_as_zero(): void
    {
        $this->stubAnalysis(['audio' => ['channels' => 2], 'mime_type' => 'audio/mpeg']);
        $validator = Validator::make(['media' => UploadedFile::fake()->create('song.mp3')], ['media' => [MediaRule::audio()->maxDuration(120)]]);
        self::assertTrue($validator->fails());
        self::assertStringContainsString('duration metadata', $validator->errors()->first('media'));
    }

    public function test_analysis_errors_fail_validation_even_when_metadata_is_present(): void
    {
        $this->stubAnalysis(['error' => ['Truncated file'], 'audio' => ['channels' => 2]]);
        self::assertTrue(Validator::make(['media' => UploadedFile::fake()->create('song.mp3')], ['media' => [MediaRule::audio()]])->fails());
    }

    public function test_russian_messages_are_available(): void
    {
        $this->app->setLocale('ru');
        $this->stubAnalysis(['audio' => ['channels' => 2], 'playtime_seconds' => 121]);
        $validator = Validator::make(['media' => UploadedFile::fake()->create('song.mp3')], ['media' => [MediaRule::audio()->maxDuration(120)]]);
        self::assertTrue($validator->fails());
        self::assertStringContainsString('не должна превышать 120 секунд', $validator->errors()->first('media'));
    }

    public function test_non_file_values_are_rejected_without_analysis(): void
    {
        $analyzer = \Mockery::mock(MediaAnalyzerServiceInterface::class);
        $analyzer->shouldNotReceive('uploadFile');
        $this->app->instance(MediaAnalyzerServiceInterface::class, $analyzer);
        self::assertTrue(Validator::make(['media' => 'video.mp4'], ['media' => [MediaRule::video()]])->fails());
    }

    public function test_invalid_rule_configuration_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MediaRule::video()->minResolution(0, 720);
    }
    public function test_custom_attribute_names_are_preserved(): void
    {
        $this->stubAnalysis(['audio' => ['channels' => 1], 'playtime_seconds' => 121]);
        $validator = Validator::make(
            ['media' => UploadedFile::fake()->create('song.mp3')],
            ['media' => [MediaRule::audio()->maxDuration(120)]],
            [], ['media' => 'uploaded recording']
        );
        self::assertTrue($validator->fails());
        self::assertStringContainsString('uploaded recording', $validator->errors()->first('media'));
    }

    public function test_warnings_do_not_fail_an_otherwise_valid_file(): void
    {
        $this->stubAnalysis(['audio' => ['channels' => 1], 'warning' => ['Recoverable warning']]);
        self::assertTrue(Validator::make(['media' => UploadedFile::fake()->create('song.mp3')], ['media' => [MediaRule::audio()]])->passes());
    }

    public function test_non_finite_duration_limits_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MediaRule::audio()->maxDuration(NAN);
    }

}
