<?php

return [

    'paths' => ['api/*'],  // CORS対象

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    // 環境変数からカンマ区切りで読ませる（例: http://127.0.0.1:8000,https://xn--hck1ajf9e6666bhk3a.com）
    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '')))),
    'allowed_origins_patterns' => [],

    // プリフライトで必要なヘッダを通す
    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'X-Suggest-Key', 'Authorization', '*'],
    'exposed_headers' => [],

    // プリフライトのキャッシュ秒数（任意）
    'max_age' => 86400,

    // Cookie を使わない運用なら false でOK。将来 Cookie を使うなら true に変更。
    'supports_credentials' => false,
];
