<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => [
        'api/*',
        'oauth/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Local Vite development
        'http://localhost:5173',

        // Nelity staging
        'https://staging--entouche-inventory.netlify.app',

        // Netlify production
        'https://entouche-inventory.netlify.app',
    ],

    /*
     * Allow Netlify deploy previews such as:
     * https://deploy-preview-34--entouche-inventory.netlify.app
     */
    'allowed_origins_patterns' => [
        '#^https://deploy-preview-\d+--entouche-inventory\.netlify\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
