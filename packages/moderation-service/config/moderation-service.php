<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Moderation Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the Moderation Service package
    |
    */

    'default_provider' => env('MODERATION_DEFAULT_PROVIDER', 'profanity_filter'),

    'auto_approve_threshold' => env('MODERATION_AUTO_APPROVE_THRESHOLD', 0.8),
    'auto_reject_threshold' => env('MODERATION_AUTO_REJECT_THRESHOLD', 0.3),

    'profanity_filter' => [
        'enabled' => env('PROFANITY_FILTER_ENABLED', true),
        'strict_mode' => env('PROFANITY_FILTER_STRICT_MODE', true),
        'custom_words_file' => env('PROFANITY_CUSTOM_WORDS_FILE', null),
    ],

    'content_analysis' => [
        'check_personal_info' => env('MODERATION_CHECK_PERSONAL_INFO', true),
        'check_inappropriate_topics' => env('MODERATION_CHECK_INAPPROPRIATE_TOPICS', true),
        'check_complexity' => env('MODERATION_CHECK_COMPLEXITY', true),
        'max_word_count' => env('MODERATION_MAX_WORD_COUNT', 200),
        'max_avg_word_length' => env('MODERATION_MAX_AVG_WORD_LENGTH', 8),
    ],

    'logging' => [
        'log_all_moderations' => env('MODERATION_LOG_ALL', false),
        'log_rejections' => env('MODERATION_LOG_REJECTIONS', true),
        'log_reviews' => env('MODERATION_LOG_REVIEWS', true),
    ],

    'caching' => [
        'enabled' => env('MODERATION_CACHE_ENABLED', true),
        'ttl' => env('MODERATION_CACHE_TTL', 1800), // 30 minutes
    ],
];
