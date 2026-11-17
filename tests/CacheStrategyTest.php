<?php

namespace Plutuss\Tests;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use JamesHeinrich\GetID3\GetID3;
use Plutuss\Cache\CacheStrategy;
use Plutuss\Services\ConfigurableMediaAnalyzerInterface;
use Plutuss\Services\MediaAnalyzerService;

class CacheStrategyTest extends PackageTestCase
{
    private function storage(): Filesystem
    {
        $disk = \Mockery::mock(Filesystem::class);
        Storage::shouldReceive('disk')->with('media')->andReturn($disk);
        $disk->shouldReceive('exists')->with('tone.wav')->andReturnTrue();
        $disk->shouldReceive('size')->with('tone.wav')->andReturn(strlen($this->wav()));
        return $disk;
    }

    private function stream(): mixed
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $this->wav());
        rewind($stream);
        return $stream;
    }

    public function test_metadata_cache_hit_does_not_open_the_file(): void
    {
        $disk = $this->storage();
        $disk->shouldReceive('lastModified')->twice()->with('tone.wav')->andReturn(1000);
        $disk->shouldReceive('readStream')->once()->with('tone.wav')->andReturn($this->stream());
        $service = app(ConfigurableMediaAnalyzerInterface::class)->setCacheStrategy(CacheStrategy::Metadata);
        self::assertSame(1.0, $service->fromLocalFile('tone.wav', 'media')->getDurationSeconds());
        self::assertSame(1.0, $service->fromLocalFile('tone.wav', 'media')->getDurationSeconds());
    }

    public function test_metadata_timestamp_changes_invalidate_cache(): void
    {
        $disk = $this->storage();
        $disk->shouldReceive('lastModified')->twice()->andReturn(1000, 1001);
        $disk->shouldReceive('readStream')->twice()->andReturnUsing(fn () => $this->stream());
        $engine = \Mockery::mock(GetID3::class)->makePartial();
        $engine->shouldReceive('analyze')->twice()->andReturn(['playtime_seconds' => 1], ['playtime_seconds' => 2]);
        $service = (new MediaAnalyzerService($engine))->setCacheStrategy('metadata');
        self::assertSame(1.0, $service->fromLocalFile('tone.wav', 'media')->getDurationSeconds());
        self::assertSame(2.0, $service->fromLocalFile('tone.wav', 'media')->getDurationSeconds());
    }

    public function test_explicit_object_versions_replace_last_modified_lookup(): void
    {
        $disk = $this->storage();
        $disk->shouldNotReceive('lastModified');
        $disk->shouldReceive('readStream')->twice()->andReturnUsing(fn () => $this->stream());
        $engine = \Mockery::mock(GetID3::class)->makePartial();
        $engine->shouldReceive('analyze')->twice()->andReturn(['playtime_seconds' => 1], ['playtime_seconds' => 2]);
        $service = (new MediaAnalyzerService($engine))->setCacheStrategy('metadata');
        self::assertSame(1.0, $service->fromLocalFile('tone.wav', 'media', 'version-1')->getDurationSeconds());
        self::assertSame(1.0, $service->fromLocalFile('tone.wav', 'media', 'version-1')->getDurationSeconds());
        self::assertSame(2.0, $service->fromLocalFile('tone.wav', 'media', 'version-2')->getDurationSeconds());
    }

    public function test_none_strategy_runs_analysis_every_time(): void
    {
        $disk = $this->storage();
        $disk->shouldNotReceive('lastModified');
        $disk->shouldReceive('readStream')->twice()->andReturnUsing(fn () => $this->stream());
        $engine = \Mockery::mock(GetID3::class)->makePartial();
        $engine->shouldReceive('analyze')->twice()->andReturn(['playtime_seconds' => 1], ['playtime_seconds' => 2]);
        $service = (new MediaAnalyzerService($engine))->setCacheStrategy('none');
        self::assertSame(1.0, $service->fromLocalFile('tone.wav', 'media')->getDurationSeconds());
        self::assertSame(2.0, $service->fromLocalFile('tone.wav', 'media')->getDurationSeconds());
    }

    public function test_global_cache_switch_takes_precedence_over_strategy(): void
    {
        config(['getid3.cache' => false]);
        $disk = $this->storage();
        $disk->shouldNotReceive('lastModified');
        $disk->shouldReceive('readStream')->twice()->andReturnUsing(fn () => $this->stream());
        $service = app(ConfigurableMediaAnalyzerInterface::class)->setCacheStrategy('metadata');
        self::assertSame(1.0, $service->fromLocalFile('tone.wav', 'media')->getDurationSeconds());
        self::assertSame(1.0, $service->fromLocalFile('tone.wav', 'media')->getDurationSeconds());
    }

    public function test_strategy_can_be_selected_from_configuration(): void
    {
        config(['getid3.cache_strategy' => 'metadata']);
        $disk = $this->storage();
        $disk->shouldReceive('lastModified')->twice()->andReturn(1000);
        $disk->shouldReceive('readStream')->once()->andReturn($this->stream());
        $service = app(ConfigurableMediaAnalyzerInterface::class);
        $service->fromLocalFile('tone.wav', 'media');
        self::assertSame(1.0, $service->fromLocalFile('tone.wav', 'media')->getDurationSeconds());
    }

    public function test_non_seekable_storage_stream_is_buffered_and_closed(): void
    {
        $disk = $this->storage();
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('This fixture uses a Unix socket pair.');
        }
        [$writer, $source] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        fwrite($writer, $this->wav());
        fclose($writer);
        try {
            self::assertFalse(stream_get_meta_data($source)['seekable']);
            $disk->shouldReceive('readStream')->once()->andReturn($source);
            $service = app(ConfigurableMediaAnalyzerInterface::class);
            self::assertSame(1.0, $service->fromLocalFile('tone.wav', 'media')->getDurationSeconds());
            self::assertFalse(is_resource($source));
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
        }
    }

    public function test_unknown_strategy_is_not_silently_accepted(): void
    {
        $this->expectException(\ValueError::class);
        app(ConfigurableMediaAnalyzerInterface::class)->setCacheStrategy('fast-ish');
    }
}
