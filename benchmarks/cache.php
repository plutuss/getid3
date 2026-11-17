<?php

// Run against a Laravel application that has this package installed.
// php benchmarks/cache.php /path/to/laravel relative/media.wav disk iterations
$basePath = $argv[1] ?? '';
$path = $argv[2] ?? '';
if (!is_file($basePath . '/bootstrap/app.php') || $path === '') {
    fwrite(STDERR, "Usage: php benchmarks/cache.php /path/to/laravel relative/media.wav [disk] [iterations]\n");
    exit(1);
}
require $basePath . '/vendor/autoload.php';
$app = require $basePath . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$disk = $argv[3] ?? config('filesystems.default');
$iterations = max(2, (int) ($argv[4] ?? 10));
$original = config('getid3');
try {
    config(['getid3.cache' => true, 'getid3.cache_time' => 60, 'getid3.key_prefix' => 'getid3-benchmark-' . bin2hex(random_bytes(8))]);
    foreach (\Plutuss\Cache\CacheStrategy::cases() as $strategy) {
        $service = $app->make(\Plutuss\Services\ConfigurableMediaAnalyzerInterface::class)->setCacheStrategy($strategy);
        memory_reset_peak_usage();
        $start = hrtime(true);
        $service->fromLocalFile($path, $disk)->throwIfErrors();
        $cold = (hrtime(true) - $start) / 1e6;
        $start = hrtime(true);
        for ($index = 1; $index < $iterations; $index++) {
            $service->fromLocalFile($path, $disk)->throwIfErrors();
        }
        $warm = (hrtime(true) - $start) / 1e6 / ($iterations - 1);
        printf("%s: first %.3f ms; repeated mean %.3f ms; peak %.2f MiB\n", $strategy->value, $cold, $warm, memory_get_peak_usage(true) / 1048576);
    }
} finally {
    config(['getid3' => $original]);
}
