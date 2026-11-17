<?php

namespace Plutuss\Download;

use GuzzleHttp\Psr7\Uri;
use Plutuss\Exceptions\GetID3Exception;

class UrlPolicy
{
    /** @return array{url: string, host: string, authority: string} */
    public function prepare(string $url, DownloadOptions $options): array
    {
        try {
            $uri = new Uri($url);
        } catch (\InvalidArgumentException $exception) {
            throw new GetID3Exception('Invalid media URL.', 0, $exception);
        }
        $host = strtolower(rtrim(trim($uri->getHost(), '[]'), '.'));
        if (!in_array($uri->getScheme(), ['http', 'https'], true) || $host === '' || preg_match('/[^\x21-\x7e]/', $host)) {
            throw new GetID3Exception('Only absolute HTTP and HTTPS media URLs are supported.');
        }
        $allowed = array_map(fn (string $value) => strtolower(rtrim(trim($value, '[]'), '.')), $options->allowedHosts);
        if ($allowed !== [] && !in_array($host, $allowed, true)) {
            throw new GetID3Exception('Media URL host is not allowed.');
        }
        $authority = $uri->getHost() . ($uri->getPort() !== null ? ':' . $uri->getPort() : '');
        if (!$options->allowPrivateNetworks) {
            $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : $this->resolve($host);
            if ($addresses === []) {
                throw new GetID3Exception('Unable to resolve media URL host.');
            }
            foreach ($addresses as $address) {
                if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE)) {
                    throw new GetID3Exception('Media URL must resolve exclusively to public IP addresses.');
                }
            }
            // Connect to the approved address, preventing a second DNS lookup.
            $ip = $addresses[0];
            $uri = $uri->withHost(str_contains($ip, ':') ? '[' . $ip . ']' : $ip);
        }
        return ['url' => (string) $uri->withFragment(''), 'host' => $host, 'authority' => $authority];
    }

    /** @return list<string> */
    protected function resolve(string $host): array
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        $addresses = [];
        foreach ($records ?: [] as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;
            if (is_string($address)) {
                $addresses[] = $address;
            }
        }
        return array_values(array_unique($addresses));
    }
}
