<?php

namespace Plutuss\Download;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Plutuss\Exceptions\GetID3Exception;

class Downloader
{
    public function __construct(private ?UrlPolicy $policy = null)
    {
        $this->policy ??= new UrlPolicy();
    }

    /** @return resource Caller owns the returned temporary stream. */
    public function download(string $url, DownloadOptions $options): mixed
    {
        $deadline = $options->timeout === null ? null : microtime(true) + $options->timeout;
        $buffer = tmpfile();
        if ($buffer === false) {
            throw new GetID3Exception('Unable to create download buffer.');
        }
        try {
            for ($redirects = 0; ; $redirects++) {
                $target = $this->policy->prepare($url, $options);
                $remaining = $this->remaining($deadline);
                [$stream, $headers] = $this->open($target['url'], [
                    'http' => [
                        'timeout' => min($options->connectTimeout, $remaining),
                        'follow_location' => 0,
                        'ignore_errors' => true,
                        'header' => ['Host: ' . $target['authority'], 'Connection: close', 'Accept-Encoding: identity'],
                    ],
                    'ssl' => ['peer_name' => $target['host'], 'verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true],
                ]);
                if (!is_resource($stream)) {
                    throw new GetID3Exception('Unable to download media file.');
                }
                try {
                    [$status, $fields] = $this->headers($headers);
                    if (in_array($status, [301, 302, 303, 307, 308], true)) {
                        if ($redirects >= $options->maxRedirects || !isset($fields['location'])) {
                            throw new GetID3Exception('Media download redirect limit reached or Location is missing.');
                        }
                        try {
                            $url = (string) UriResolver::resolve(new Uri($url), new Uri($fields['location']));
                        } catch (\InvalidArgumentException $exception) {
                            throw new GetID3Exception('Invalid download redirect.', 0, $exception);
                        }
                        continue;
                    }
                    if ($status < 200 || $status >= 300) {
                        throw new GetID3Exception('Media download returned HTTP ' . $status . '.');
                    }
                    $length = isset($fields['content-length']) && ctype_digit($fields['content-length'])
                        ? (float) $fields['content-length'] : null;
                    if ($options->maxBytes !== null && $length !== null && $length > $options->maxBytes) {
                        throw new GetID3Exception('Media download exceeds the configured byte limit.');
                    }
                    $bytes = 0;
                    while (!feof($stream)) {
                        $seconds = min($options->readTimeout, $this->remaining($deadline));
                        @stream_set_timeout($stream, (int) $seconds, (int) (($seconds - (int) $seconds) * 1_000_000));
                        $chunk = fread($stream, 8192);
                        $metadata = stream_get_meta_data($stream);
                        if ($chunk === false || ($metadata['timed_out'] ?? false)) {
                            throw new GetID3Exception('Media download failed or timed out.');
                        }
                        $this->remaining($deadline);
                        $bytes += strlen($chunk);
                        if ($options->maxBytes !== null && $bytes > $options->maxBytes) {
                            throw new GetID3Exception('Media download exceeds the configured byte limit.');
                        }
                        for ($offset = 0; $offset < strlen($chunk); $offset += $written) {
                            $written = fwrite($buffer, substr($chunk, $offset));
                            if ($written === false || $written === 0) {
                                throw new GetID3Exception('Unable to buffer media download.');
                            }
                        }
                    }
                    if ($length !== null && !isset($fields['transfer-encoding']) && $bytes != $length) {
                        throw new GetID3Exception('Media download size does not match Content-Length.');
                    }
                    rewind($buffer);
                    return $buffer;
                } finally {
                    fclose($stream);
                }
            }
        } catch (\Throwable $exception) {
            fclose($buffer);
            throw $exception;
        }
    }

    /** @return array{mixed, list<string>} */
    protected function open(string $url, array $options): array
    {
        $stream = @fopen($url, 'rb', false, stream_context_create($options));
        $headers = is_resource($stream) ? (stream_get_meta_data($stream)['wrapper_data'] ?? []) : [];
        return [$stream, is_array($headers) ? $headers : []];
    }

    /** @return array{int, array<string, string>} */
    private function headers(array $headers): array
    {
        $status = 200;
        $fields = [];
        foreach ($headers as $line) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $line, $match)) {
                $status = (int) $match[1];
                $fields = [];
            } elseif (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $fields[strtolower(trim($key))] = trim($value);
            }
        }
        return [$status, $fields];
    }

    private function remaining(?float $deadline): float
    {
        if ($deadline === null) {
            return INF;
        }
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0) {
            throw new GetID3Exception('Media download exceeded its total timeout.');
        }
        return $remaining;
    }
}
