<?php

namespace Plutuss\Tests;

use Plutuss\Download\DownloadOptions;
use Plutuss\Download\UrlPolicy;
use Plutuss\Exceptions\GetID3Exception;
use Plutuss\Services\MediaAnalyzerServiceInterface;
use Illuminate\Support\Facades\Storage;

class DownloaderTest extends PackageTestCase
{
    private function failsDownload(FakeDownloader $downloader, DownloadOptions $options, string $message): void
    {
        try {
            $downloader->download('https://media.test/clip', $options);
            self::fail('Expected download failure.');
        } catch (GetID3Exception $exception) {
            self::assertStringContainsString($message, $exception->getMessage());
        }
        foreach ($downloader->streams as $stream) {
            self::assertFalse(is_resource($stream));
        }
    }

    public function test_download_returns_rewinded_buffer_and_closes_response(): void
    {
        $downloader = new FakeDownloader();
        $downloader->responses = [[$this->wav(), ['HTTP/1.1 200 OK', 'Content-Length: 8044']]];
        $buffer = $downloader->download('https://media.test/tone.wav', new DownloadOptions(maxBytes: 8044));
        try {
            self::assertSame($this->wav(), stream_get_contents($buffer));
            self::assertFalse(is_resource($downloader->streams[0]));
        } finally {
            fclose($buffer);
        }
    }

    public function test_streaming_limit_does_not_depend_on_content_length(): void
    {
        $downloader = new FakeDownloader();
        $downloader->responses = [[str_repeat('x', 10000), ['HTTP/1.1 200 OK']]];
        $this->failsDownload($downloader, new DownloadOptions(maxBytes: 8192), 'byte limit');
    }

    public function test_declared_oversize_is_rejected(): void
    {
        $downloader = new FakeDownloader();
        $downloader->responses = [['', ['HTTP/1.1 200 OK', 'Content-Length: 10000']]];
        $this->failsDownload($downloader, new DownloadOptions(maxBytes: 100), 'byte limit');
    }

    public function test_truncated_response_is_rejected(): void
    {
        $downloader = new FakeDownloader();
        $downloader->responses = [['abc', ['HTTP/1.1 200 OK', 'Content-Length: 10']]];
        $this->failsDownload($downloader, new DownloadOptions(), 'Content-Length');
    }

    public function test_http_errors_do_not_become_media_files(): void
    {
        $downloader = new FakeDownloader();
        $downloader->responses = [['Not found', ['HTTP/1.1 404 Not Found']]];
        $this->failsDownload($downloader, new DownloadOptions(), 'HTTP 404');
    }

    public function test_relative_redirects_are_resolved(): void
    {
        $downloader = new FakeDownloader();
        $downloader->responses = [
            ['', ['HTTP/1.1 302 Found', 'Location: ../audio/tone.wav']],
            ['tone', ['HTTP/1.1 200 OK']],
        ];
        $buffer = $downloader->download('https://media.test/video/start', new DownloadOptions());
        fclose($buffer);
        self::assertSame('https://media.test/audio/tone.wav', $downloader->requests[1][0]);
        self::assertSame(0, $downloader->requests[0][1]['http']['follow_location']);
    }

    public function test_redirect_hosts_are_checked_before_the_next_request(): void
    {
        $downloader = new FakeDownloader();
        $downloader->responses = [['', ['HTTP/1.1 302 Found', 'Location: https://other.test/file']]];
        $this->failsDownload($downloader, new DownloadOptions(allowedHosts: ['media.test']), 'not allowed');
        self::assertCount(1, $downloader->requests);
    }

    public function test_redirect_to_private_address_is_rejected(): void
    {
        $policy = new class extends UrlPolicy {
            protected function resolve(string $host): array { return ['93.184.216.34']; }
        };
        $downloader = new FakeDownloader($policy);
        $downloader->responses = [['', ['HTTP/1.1 302 Found', 'Location: http://127.0.0.1/private']]];
        $this->failsDownload($downloader, new DownloadOptions(allowPrivateNetworks: false), 'public IP');
        self::assertCount(1, $downloader->requests);
    }

    public function test_public_address_is_pinned_and_tls_verification_uses_original_host(): void
    {
        $policy = new class extends UrlPolicy {
            protected function resolve(string $host): array { return ['93.184.216.34']; }
        };
        $downloader = new FakeDownloader($policy);
        $downloader->responses = [['tone', ['HTTP/1.1 200 OK']]];
        fclose($downloader->download('https://media.test/file', new DownloadOptions(allowPrivateNetworks: false)));
        [$url, $context] = $downloader->requests[0];
        self::assertSame('https://93.184.216.34/file', $url);
        self::assertSame('media.test', $context['ssl']['peer_name']);
        self::assertTrue($context['ssl']['verify_peer_name']);
        self::assertContains('Host: media.test', $context['http']['header']);
    }

    public function test_private_and_reserved_address_families_are_rejected(): void
    {
        foreach (['127.0.0.1', '10.0.0.1', '169.254.169.254', '100.64.0.1', '[::1]', '[::ffff:127.0.0.1]', '[fc00::1]'] as $host) {
            try {
                (new UrlPolicy())->prepare('http://' . $host . '/', new DownloadOptions(allowPrivateNetworks: false));
                self::fail('Accepted non-public host: ' . $host);
            } catch (GetID3Exception $exception) {
                self::assertStringContainsString('public IP', $exception->getMessage());
            }
        }
    }

    public function test_mixed_public_and_private_dns_answers_are_rejected(): void
    {
        $policy = new class extends UrlPolicy {
            protected function resolve(string $host): array { return ['93.184.216.34', '127.0.0.1']; }
        };
        $this->expectException(GetID3Exception::class);
        $policy->prepare('https://media.test/file', new DownloadOptions(allowPrivateNetworks: false));
    }

    public function test_redirect_budget_is_enforced(): void
    {
        $downloader = new FakeDownloader();
        $downloader->responses = [['', ['HTTP/1.1 302 Found', 'Location: /loop']]];
        $this->failsDownload($downloader, new DownloadOptions(maxRedirects: 0), 'redirect limit');
    }

    public function test_total_timeout_is_enforced_after_opening_response(): void
    {
        $downloader = new FakeDownloader();
        $downloader->delayMicroseconds = 10000;
        $downloader->responses = [['tone', ['HTTP/1.1 200 OK']]];
        $this->failsDownload($downloader, new DownloadOptions(timeout: 0.001), 'total timeout');
    }

    public function test_service_download_options_apply_before_storage_write(): void
    {
        Storage::fake('media');
        $downloader = new FakeDownloader();
        $downloader->responses = [[$this->wav(), ['HTTP/1.1 200 OK']]];
        $this->app->instance(\Plutuss\Download\Downloader::class, $downloader);
        try {
            app(MediaAnalyzerServiceInterface::class)->setDisk('media')
                ->setDownloadOptions(new DownloadOptions(maxBytes: 100))->fromUrl('https://media.test/tone');
            self::fail('Expected byte limit.');
        } catch (GetID3Exception $exception) {
            self::assertStringContainsString('byte limit', $exception->getMessage());
        }
        self::assertSame([], Storage::disk('media')->allFiles());
    }

    public function test_invalid_options_fail_early(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DownloadOptions(timeout: INF);
    }
    public function test_partial_published_download_config_keeps_defaults(): void
    {
        config(['getid3.download' => ['timeout' => 12]]);
        $options = app(DownloadOptions::class);
        self::assertSame(12.0, $options->timeout);
        self::assertSame(30.0, $options->connectTimeout);
        self::assertSame(20, $options->maxRedirects);
        self::assertNull($options->maxBytes);
    }

    public function test_legacy_custom_managers_are_not_required_to_implement_new_options(): void
    {
        $manager = \Mockery::mock(\Plutuss\Services\MediaManagerServiceInterface::class);
        $service = new \Plutuss\Services\MediaAnalyzerService(new \JamesHeinrich\GetID3\GetID3(), $manager);
        $this->expectException(GetID3Exception::class);
        $this->expectExceptionMessage('does not support download options');
        $service->setDownloadOptions(new DownloadOptions());
    }

    public function test_default_options_preserve_unbounded_total_downloads(): void
    {
        $options = app(DownloadOptions::class);
        self::assertNull($options->timeout);
        self::assertNull($options->maxBytes);
        self::assertSame(30.0, $options->readTimeout);
        self::assertSame(30.0, $options->connectTimeout);
        self::assertTrue($options->allowPrivateNetworks);
    }

}
