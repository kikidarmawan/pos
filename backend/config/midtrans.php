<?php

return [
    'server_key' => env('MIDTRANS_SERVER_KEY', ''),
    'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'finish_url' => env('MIDTRANS_FINISH_URL', env('APP_URL', 'http://localhost') . '/subscription/return'),
    'error_url' => env('MIDTRANS_ERROR_URL', env('APP_URL', 'http://localhost') . '/subscription/return?status=error'),
    'pending_url' => env('MIDTRANS_PENDING_URL', env('APP_URL', 'http://localhost') . '/subscription/return?status=pending'),
];
