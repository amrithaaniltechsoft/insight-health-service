<?php

return [
    'paths' => ['*'],

    'allowed_methods' => ['*'],

    // Every origin the frontend is served from has to be listed here, or the
    // browser blocks the call before it reaches Laravel. Vercel gives each
    // deployment its own domain, so both the production one and the per-deploy
    // preview domains are allowed.
    'allowed_origins' => array_values(array_filter([
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        env('FRONTEND_URL'),
    ])),

    // Covers insight-silk.vercel.app and any insight-silk-*.vercel.app preview
    // deployment. Without this, Add Person works locally and fails on Vercel
    // with an opaque CORS error in the console rather than a readable message.
    'allowed_origins_patterns' => [
        '#^https://([a-z0-9-]+\.)*vercel\.app$#i',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];