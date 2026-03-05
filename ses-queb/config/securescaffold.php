<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Secure Scaffold Configuration
    |--------------------------------------------------------------------------
    */

    'output_path' => env('SCAFFOLD_OUTPUT_PATH', storage_path('scaffolds')),

    'templates_path' => env('TEMPLATES_PATH', resource_path('templates')),

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect_uri' => env('GITHUB_REDIRECT_URI'),
    ],

    'security' => [
        'scan_timeout' => env('SECURITY_SCAN_TIMEOUT', 300),
        'allowed_licenses' => ['MIT', 'Apache-2.0', 'BSD-3-Clause', 'ISC'],
    ],

    'ai' => [
        'enabled' => env('AI_FIXES_ENABLED', false),
        'provider' => env('AI_PROVIDER', 'openai'),
        'api_key' => env('AI_API_KEY'),
    ],
];
