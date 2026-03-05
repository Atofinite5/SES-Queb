<?php

/**
 * SES-Queb SDK Configuration
 *
 * Customize the SES-Queb API client behavior
 */

return [
    /*
    |--------------------------------------------------------------------------
    | API URL
    |--------------------------------------------------------------------------
    |
    | The base URL of your SES-Queb API instance.
    | Default: https://ses-queb-api.render.com/api/v1
    |
    */
    'api_url' => env('SES_QUEB_API_URL', 'https://ses-queb-api.render.com/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for API requests.
    | Default: 30 seconds
    |
    */
    'timeout' => env('SES_QUEB_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Authentication Token
    |--------------------------------------------------------------------------
    |
    | Optional bearer token for authenticated requests.
    | Leave empty if your API is public.
    |
    */
    'auth_token' => env('SES_QUEB_AUTH_TOKEN', null),
];
