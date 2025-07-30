<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Question Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the Question Service package
    |
    */

    'rate_limiting' => [
        'questions_per_hour' => env('QUESTIONS_PER_HOUR', 10),
        'questions_per_day' => env('QUESTIONS_PER_DAY', 50),
    ],

    'processing' => [
        'timeout_seconds' => env('QUESTION_PROCESSING_TIMEOUT', 30),
        'retry_attempts' => env('QUESTION_RETRY_ATTEMPTS', 3),
    ],

    'moderation' => [
        'auto_approve_threshold' => env('MODERATION_AUTO_APPROVE_THRESHOLD', 0.8),
        'auto_reject_threshold' => env('MODERATION_AUTO_REJECT_THRESHOLD', 0.3),
    ],
];
