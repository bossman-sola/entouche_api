<?php

return [
    'paths' => ['api/*', 'oauth/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://entouche-inventory.netlify.app',
    ],

    'allowed_origins_patterns' => [
        '#^https://deploy-preview-\d+--entouche-inventory\.netlify\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
