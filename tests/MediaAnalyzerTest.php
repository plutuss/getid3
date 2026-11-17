<?php

namespace Plutuss\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use Plutuss\DTO\MediaAnalyzerDTO;
use Plutuss\Exceptions\GetID3Exception;
use Plutuss\Providers\GetId3ServiceProvider;
use Plutuss\Services\MediaAnalyzerServiceInterface;
use Plutuss\Services\MediaManagerServiceInterface;

class MediaAnalyzerTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [GetId3ServiceProvider::class];
    }

    private function wav(int $seconds = 1): string
    {
        $data = str_repeat("\x80", 8000 * $seconds);
        return 'RIFF' . pack('V', 36 + strlen($data)) . 'WAVEfmt '
            . pack('VvvVVvv', 16, 1, 1, 8000, 8000, 1, 8)
            . 'data' . pack('V', strlen($data)) . $data;
    }

    public function test_default_configuration_and_service_instances(): void
    {
        self::assertTrue(config('getid3.cache'));
        self::assertSame('getid3', config('getid3.key_prefix'));
        self::assertNotSame(app(MediaAnalyzerServiceInterface::class), app(MediaAnalyzerServiceInterface::class));
        self::assertNotSame(app(MediaManagerServiceInterface::class), app(MediaManagerServiceInterface::class));
    }

    public function test_real_wav_analysis_from_storage_and_upload(): void
    {
        Storage::fake('media');
        Storage::disk('media')->put('tone.wav', $this->wav());
        $service = app(MediaAnalyzerServiceInterface::class);
        $result = $service->fromLocalFile('tone.wav', 'media');
        self::assertEquals(1, $result->getPlaytimeSeconds());
        self::assertSame('00:00:01', $result->getDuration());
        self::assertSame('wav', $result->getFileFormat());
        self::assertSame(8044, $result->getFileSize());

        $upload = UploadedFile::fake()->createWithContent('tone.wav', $this->wav(2));
        self::assertEquals(2, $service->uploadFile($upload)->getPlaytimeSeconds());
    }

    public function test_cache_changes_when_same_size_file_is_replaced(): void
    {
        Storage::fake('media');
        $service = app(MediaAnalyzerServiceInterface::class);
        Storage::disk('media')->put('tone.wav', $this->wav());
        self::assertEquals(1, $service->fromLocalFile('tone.wav', 'media')->getPlaytimeSeconds());
        // Change sample and byte rates without changing file size.
        $replacement = substr_replace($this->wav(), pack('VV', 4000, 4000), 24, 8);
        Storage::disk('media')->put('tone.wav', $replacement);
        self::assertEquals(2, $service->fromLocalFile('tone.wav', 'media')->getPlaytimeSeconds());
    }

    public function test_missing_file_throws_package_exception(): void
    {
        Storage::fake('media');
        $this->expectException(GetID3Exception::class);
        app(MediaAnalyzerServiceInterface::class)->fromLocalFile('missing.wav', 'media');
    }

    public function test_missing_path_throws_package_exception(): void
    {
        $this->expectException(GetID3Exception::class);
        app(MediaAnalyzerServiceInterface::class)->fromLocalFile();
    }

    public function test_duration_is_independent_of_timezone_and_supports_long_files(): void
    {
        $timezone = date_default_timezone_get();
        try {
            date_default_timezone_set('Europe/Berlin');
            $dto = new MediaAnalyzerDTO(collect(['playtime_seconds' => 90061.5]));
            self::assertSame('25:01:01', $dto->getDuration());
        } finally {
            date_default_timezone_set($timezone);
        }
    }

    public function test_metadata_and_nested_access(): void
    {
        $dto = new MediaAnalyzerDTO(collect([
            'comments' => ['artist' => ['Artist'], 'genre' => ['Rock'], 'title' => ['Title']],
            'video' => ['resolution_x' => 1920, 'resolution_y' => 1080],
        ]));
        self::assertSame(['Artist'], $dto->getArtist());
        self::assertSame(['Rock'], $dto->getGenres());
        self::assertSame('Title', $dto->getTitle());
        self::assertSame(1920, $dto->getNestedValue('video.resolution_x'));
        self::assertSame('fallback', $dto->getNestedValue('missing', 'fallback'));
        self::assertSame(1080, $dto->video->get('resolution_y'));
        self::assertSame($dto->all(), $dto->getAllInfo());
        self::assertSame('', (new MediaAnalyzerDTO(collect()))->getTitle());
    }

    public function test_manager_rejects_non_http_urls(): void
    {
        $this->expectException(GetID3Exception::class);
        app(MediaManagerServiceInterface::class)->setUrl('file:///etc/passwd')->handler();
    }
    public function test_repeated_url_downloads_keep_directory_and_name_stable(): void
    {
        Storage::fake('media');
        HttpMediaStream::$contents = $this->wav();
        stream_wrapper_unregister('http');
        stream_wrapper_register('http', HttpMediaStream::class);
        try {
            $service = app(MediaAnalyzerServiceInterface::class)
                ->setDisk('media')->setFilePath('music')->setFileName('tone')
                ->saveFileFromUrl();
            self::assertEquals(1, $service->fromUrl('http://media.test/first')->getPlaytimeSeconds());
            $files = Storage::disk('media')->allFiles();
            self::assertCount(1, $files);
            self::assertStringStartsWith('music/tone.', $files[0]);
            self::assertEquals(1, $service->fromUrl('http://media.test/second')->getPlaytimeSeconds());
            self::assertSame($files, Storage::disk('media')->allFiles());

            $service->saveFileFromUrl(false)->setFileName('temporary')->fromUrl('http://media.test/third');
            self::assertSame($files, Storage::disk('media')->allFiles());
        } finally {
            stream_wrapper_restore('http');
        }
    }

    public function test_temporary_download_is_deleted_when_analysis_fails(): void
    {
        Storage::fake('media');
        $manager = \Mockery::mock(MediaManagerServiceInterface::class);
        $manager->shouldReceive('setUrl')->once()->with('https://media.test/tone.wav')->andReturnSelf();
        $manager->shouldReceive('setDisk')->once()->with('media')->andReturnSelf();
        $manager->shouldReceive('handler')->once();
        $manager->shouldReceive('getPath')->once()->andReturn('missing.wav');
        $manager->shouldReceive('delete')->once();
        $service = new \Plutuss\Services\MediaAnalyzerService(new \JamesHeinrich\GetID3\GetID3(), $manager);
        $this->expectException(GetID3Exception::class);
        $service->setDisk('media')->fromUrl('https://media.test/tone.wav');
    }

    public function test_cache_separates_disks(): void
    {
        Storage::fake('first');
        Storage::fake('second');
        Storage::disk('first')->put('tone.wav', $this->wav());
        Storage::disk('second')->put('tone.wav', substr_replace($this->wav(), pack('VV', 4000, 4000), 24, 8));
        $service = app(MediaAnalyzerServiceInterface::class);
        self::assertEquals(1, $service->fromLocalFile('tone.wav', 'first')->getPlaytimeSeconds());
        self::assertEquals(2, $service->fromLocalFile('tone.wav', 'second')->getPlaytimeSeconds());
    }

    public function test_embedded_artwork_can_be_read_without_http_side_effects(): void
    {
        $headers = headers_list();
        $dto = new MediaAnalyzerDTO(collect(['comments' => ['picture' => [[
            'image_mime' => 'image/png', 'data' => 'image bytes',
        ]]]]));
        self::assertSame('image bytes', $dto->getImageData());
        self::assertSame('image/png', $dto->getImageMimeType());
        self::assertSame($headers, headers_list());
        self::assertNull((new MediaAnalyzerDTO(null))->getImageData());
    }

    public function test_manager_keeps_downloaded_bytes_available_after_deletion(): void
    {
        Storage::fake('media');
        HttpMediaStream::$contents = $this->wav();
        stream_wrapper_unregister('http');
        stream_wrapper_register('http', HttpMediaStream::class);
        try {
            $manager = app(MediaManagerServiceInterface::class)
                ->setDisk('media')->setUrl('http://media.test/tone');
            $manager->handler();
            self::assertSame($this->wav(), $manager->getFile());
            $manager->delete();
            self::assertSame([], Storage::disk('media')->allFiles());
            self::assertSame($this->wav(), $manager->getFile());
        } finally {
            stream_wrapper_restore('http');
        }
    }

    public function test_analysis_exception_closes_storage_stream(): void
    {
        config(['getid3.cache' => false]);
        Storage::fake('media');
        Storage::disk('media')->put('tone.wav', $this->wav());
        $stream = null;
        $engine = \Mockery::mock(\JamesHeinrich\GetID3\GetID3::class);
        $engine->shouldReceive('analyze')->once()->andReturnUsing(
            function ($path, $size, $name, $resource) use (&$stream) {
                $stream = $resource;
                throw new \RuntimeException('Analysis failed');
            }
        );
        $service = new \Plutuss\Services\MediaAnalyzerService($engine);
        try {
            $service->fromLocalFile('tone.wav', 'media');
            self::fail('The analysis exception should propagate.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Analysis failed', $exception->getMessage());
        }
        self::assertNotNull($stream);
        self::assertFalse(is_resource($stream));
    }

    public function test_unchanged_file_uses_cached_analysis(): void
    {
        Storage::fake('media');
        Storage::disk('media')->put('tone.wav', $this->wav());
        $engine = \Mockery::mock(\JamesHeinrich\GetID3\GetID3::class)->makePartial();
        $engine->shouldReceive('analyze')->once()->andReturn(['playtime_seconds' => 1]);
        $service = new \Plutuss\Services\MediaAnalyzerService($engine);
        self::assertEquals(1, $service->fromLocalFile('tone.wav', 'media')->getPlaytimeSeconds());
        self::assertEquals(1, $service->fromLocalFile('tone.wav', 'media')->getPlaytimeSeconds());
    }

    public function test_both_id3_versions_are_normalized_without_overwriting_existing_tags(): void
    {
        Storage::fake('media');
        Storage::disk('media')->put('tone.wav', $this->wav());
        $engine = \Mockery::mock(\JamesHeinrich\GetID3\GetID3::class)->makePartial();
        $engine->shouldReceive('analyze')->once()->andReturn([
            'encoding' => 'UTF-8',
            'id3v2' => ['comments' => ['title' => ['Old title']]],
            'id3v1' => ['comments' => ['artist' => ['Artist']]],
            'tags' => ['id3v2' => ['title' => ['Normalized title']]],
        ]);
        $dto = (new \Plutuss\Services\MediaAnalyzerService($engine))->fromLocalFile('tone.wav', 'media');
        self::assertSame('Normalized title', $dto->getTitle());
        self::assertSame(['Artist'], $dto->getArtist());
    }

}
