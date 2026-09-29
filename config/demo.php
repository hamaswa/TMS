<?php

return [
    'enabled' => (bool) env('DEMO_ACCOUNT_ENABLED', false),
    'email' => env('DEMO_ACCOUNT_EMAIL', 'demo@buynstitch.com'),
    'password' => env('DEMO_ACCOUNT_PASSWORD', 'Demo@2026'),
    'reset_time' => env('DEMO_ACCOUNT_RESET_TIME', '03:00'),
];
