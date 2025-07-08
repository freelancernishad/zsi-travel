<?php


return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Add paths your frontend will call
    'allowed_methods' => ['*'], // Allow all HTTP methods (GET, POST, etc.)
    'allowed_origins' => ['*'], // Allow Vite's default URL
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'], // Allow all headers
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // Set to true if using cookies or session-based auth
];
