<?php

return [
    'key_prefix' => 'getid3', // Prefix for cache keys
    'cache' => env('GETID3_CACHE', true), // Enable or disable caching
    'cache_time' => env('GETID3_CACHE_TIME', 86400), // 1 day
];
