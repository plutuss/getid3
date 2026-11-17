<?php

return [
    'key_prefix' => 'getid3', // Prefix for cache keys
    'cache_strategy' => env('GETID3_CACHE_STRATEGY', 'content'), // content, metadata, none
    'cache' => env('GETID3_CACHE', true), // Enable or disable caching
    'download' => [
        'max_bytes' => null,
        'timeout' => null, // Opt into a total deadline without changing 2.0 downloads.
        'read_timeout' => 30,
        'connect_timeout' => 30,
        'max_redirects' => 20,
        'allowed_hosts' => [],
        'allow_private_networks' => true, // Preserve access to internal media servers.
    ],
    'cache_time' => env('GETID3_CACHE_TIME', 86400), // 1 day
];
