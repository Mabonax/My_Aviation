<?php

return [
    'enabled' => env('WORKOS_ENABLED', false),
    'api_key' => env('WORKOS_API_KEY'),
    'client_id' => env('WORKOS_CLIENT_ID'),
    'redirect_uri' => env('WORKOS_REDIRECT_URI'),
    'api_base_url' => null,
    'mobile_redirect_uri' => env('WORKOS_MOBILE_REDIRECT_URI', 'za.co.vmt.yaw://auth/callback'),
];
