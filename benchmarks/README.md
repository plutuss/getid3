# Cache benchmark

Run the script against a Laravel application with the package installed:

```shell
php vendor/plutuss/getid3-laravel/benchmarks/cache.php /path/to/app relative/file.wav disk 10
```

The script uses a unique cache prefix and a 60-second TTL, restores configuration
on completion and never flushes the application's cache. It measures the first
analysis, average repeated analysis and total PHP peak memory. It does not compare
concurrent access, network throughput or competing libraries.

Example development run (2026-11-17): PHP 8.4.12, Laravel 13, array cache, local disk,
8 MiB generated mono PCM WAV, ten calls per strategy:

| Strategy | First call (ms) | Repeated mean (ms) | PHP peak (MiB) |
| --- | ---: | ---: | ---: |
| content | 251.107 | 83.128 | 22.00 |
| metadata | 0.817 | 0.097 | 22.00 |
| none | 0.577 | 0.809 | 22.00 |

This is an illustrative single-process run, not a performance guarantee. The
first strategy includes class-loading costs. WAV metadata analysis is cheap;
reading every byte for a content fingerprint can cost more than analyzing this
format without caching. Remote storage and complex formats can behave very
differently. Measure representative files and choose the strategy accordingly.

`CacheStrategyTest` separately verifies the stable property behind metadata mode:
an unchanged cached file performs no stream opens. Metadata mode may miss
same-size updates with unchanged timestamps; content mode remains the default.
