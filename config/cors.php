<?php

return [

    'paths' => ['api/*'],                // APIだけCORS対象

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    // 開発中は * でOK。本番は特定Originだけ列挙してください
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],
    'exposed_headers' => [],

    'max_age' => 0,
    'supports_credentials' => false,
];
