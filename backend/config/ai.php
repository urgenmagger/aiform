<?php

return [
    'enabled' => (bool) env('AI_ENABLED', false),
    'provider' => env('AI_PROVIDER', 'deepseek'),
    'api_key' => env('AI_API_KEY'),
    'base_url' => env('AI_BASE_URL', 'https://api.deepseek.com'),
    'model' => env('AI_MODEL', 'deepseek-chat'),
    'timeout_seconds' => (int) env('AI_TIMEOUT_SECONDS', 10),
];
