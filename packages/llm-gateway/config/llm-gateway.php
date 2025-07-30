<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LLM Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the LLM Gateway package
    |
    */

    'default_provider' => env('LLM_DEFAULT_PROVIDER', 'openai'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 500),
        'temperature' => env('OPENAI_TEMPERATURE', 0.7),
        'top_p' => env('OPENAI_TOP_P', 1.0),
        'frequency_penalty' => env('OPENAI_FREQUENCY_PENALTY', 0.0),
        'presence_penalty' => env('OPENAI_PRESENCE_PENALTY', 0.0),
        'timeout' => env('OPENAI_TIMEOUT', 30),
    ],

    'rate_limiting' => [
        'requests_per_minute' => env('LLM_REQUESTS_PER_MINUTE', 60),
        'requests_per_hour' => env('LLM_REQUESTS_PER_HOUR', 1000),
    ],

    'caching' => [
        'enabled' => env('LLM_CACHE_ENABLED', true),
        'ttl' => env('LLM_CACHE_TTL', 3600), // 1 hour
    ],

    'child_safety' => [
        'max_age' => env('CHILD_MAX_AGE', 16),
        'default_age' => env('CHILD_DEFAULT_AGE', 8),
        'strict_mode' => env('CHILD_STRICT_MODE', true),
    ],
];
