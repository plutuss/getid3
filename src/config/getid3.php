<?php

return [
    'key_prefix' => 'getid3', // Prefix for cache keys
    'cache_strategy' => env('GETID3_CACHE_STRATEGY', 'content'), // content, metadata, none
    'cache' => env('GETID3_CACHE', true), // Enable or disable caching
    'download' => [
        'max_bytes' => null,
        'timeout' => 30,
        'read_timeout' => 30,
        'connect_timeout' => 10,
        'max_redirects' => 5,
        'allowed_hosts' => [],
        'allow_private_networks' => true, // Preserve access to internal media servers.
    ],
    'cache_time' => env('GETID3_CACHE_TIME', 86400), // 1 day
];
