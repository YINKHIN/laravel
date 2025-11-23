<?php

return [
    // 'paths' => ['api/*', 'sanctum/csrf-cookie'],
    // 'allowed_methods' => ['*'],
    // 'allowed_origins' => [
    //     'http://localhost:3000',
    //     'http://localhost:3002',
    //     'http://127.0.0.1:3000',
    //     'http://127.0.0.1:3002',
    //     'http://localhost:5173',
    //     'https://frontend-react-q1gnl3hwz-yin-khins-projects.vercel.app',
    //     'https://frontend-react-sable.vercel.app',
    //     'https://frontend-react-git-main-yin-khins-projects.vercel.app',
    // ],
    // 'allowed_origins_patterns' => [
    //     '/https:\/\/.*\.vercel\.app/',
    // ],
    // 'allowed_headers' => ['*'],
    // 'exposed_headers' => [],
    // 'max_age' => 0,
    // 'supports_credentials' => false,
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        // Local development
        'http://localhost:3000',
        'http://localhost:3002',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3002',
        'http://localhost:5173',
        // Deployed Vercel frontend (specific domains)
        'https://frontend-react-q1gnl3hwz-yin-khins-projects.vercel.app',
        'https://frontend-react-sable.vercel.app',
        'https://frontend-react-git-main-yin-khins-projects.vercel.app',
    ],
    // Wildcard: allow any subdomain ending in .vercel.app
    'allowed_origins_patterns' => [
        '/https:\/\/.*\.vercel\.app/',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    // Change to true ONLY if using cookies / Sanctum
    'supports_credentials' => false,
];
