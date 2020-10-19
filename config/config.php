<?php

return [
    'wsdl_cache_enabled' => env('WSDL_CACHE_ENABLED', true),

    'wsdl_formatting_enabled' => env('WSDL_FORMATTING_ENABLED', false),

    'headers' => [
        'soap' => [
            'Content-Type' => 'application/soap+xml; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store'
        ],
        'wsdl' => [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store'
        ],
    ],
];
