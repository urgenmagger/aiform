<?php

return [
    'rate_limit' => [
        'limit' => (int) env('CONTACT_RATE_LIMIT', 5),
        'window_seconds' => (int) env('CONTACT_RATE_WINDOW_SECONDS', 60),
    ],
];
