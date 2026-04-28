<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('API_SIGNATURE_ENABLED', true),
    'ttl' => (int) env('API_SIGNATURE_TTL', 300),
    'clients' => [
        'h5' => [
            'secret' => (string) env('API_SIGNATURE_H5_SECRET', 'change-me-h5-signature-secret'),
        ],
        'miniapp' => [
            'secret' => (string) env('API_SIGNATURE_MINIAPP_SECRET', 'change-me-miniapp-signature-secret'),
        ],
    ],
];
