<?php

/*
 * The API is consumed by desktop clients (the Revit plugin), which do not go
 * through CORS. Only the site itself may call it from a browser, so other
 * websites cannot use the API from their pages.
 */
return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [rtrim((string) env('APP_URL', 'http://localhost'), '/')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Retry-After', 'Content-Disposition'],

    'max_age' => 0,

    'supports_credentials' => false,

];
