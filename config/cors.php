<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Allows the React frontend (localhost:3000) to communicate with this API.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:3001,http://localhost:3002,http://localhost:3003,http://127.0.0.1:3000,http://127.0.0.1:3001,http://127.0.0.1:3002,http://127.0.0.1:3003,https://frontend-flax-nine-6iu2dl9cx6.vercel.app,https://frontend-flax-nine-6iu2dl9cx6.vercel.app/')))),

    'allowed_origins_patterns' => [
        '#^http://(localhost|127\.0\.0\.1):(300[0-9]|517[0-9]|81)$#',
        '#^https://frontend-flax-nine-6iu2dl9cx6\.vercel\.app/?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
