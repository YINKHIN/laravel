<?php

return [
    /*
     * |--------------------------------------------------------------------------
     * | Cross-Origin Resource Sharing (CORS) Configuration
     * |--------------------------------------------------------------------------
     * |
     * | Here you may configure your settings for cross-origin resource sharing
     * | or "CORS". This determines what cross-origin operations may execute
     * | in web browsers. You are free to adjust these settings as needed.
     * |
     * | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
     * |
     */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:3002',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3002',
        'http://localhost:5173',
        'https://frontend-react-q1gnl3hwz-yin-khins-projects.vercel.app',
        'https://frontend-react-sable.vercel.app',
        'https://frontend-react-git-main-yin-khins-projects.vercel.app',
    ],
    'allowed_origins_patterns' => [
        '/https:\/\/.*\.vercel\.app/',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
