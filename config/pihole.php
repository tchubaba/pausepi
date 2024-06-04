<?php

declare(strict_types=1);

return [
    'boxes' => [
        'Main' => [
            'name'    => env('MAIN_PIHOLE_NAME', 'Main'),
            'ip'      => env('MAIN_PIHOLE_IP', ''),
            'api_key' => env('MAIN_PIHOLE_API_KEY', ''),
        ],
        'Secondary' => [
            'name'    => env('SECONDARY_PIHOLE_NAME', 'Secondary'),
            'ip'      => env('SECONDARY_PIHOLE_IP', ''),
            'api_key' => env('SECONDARY_PIHOLE_API_KEY', ''),
        ],
        'Alt' => [
            'name'    => env('ALT_PIHOLE_NAME', 'Alt'),
            'ip'      => env('ALT_PIHOLE_IP', ''),
            'api_key' => env('ALT_PIHOLE_API_KEY', ''),
        ],
    ],
    'min_timeout_seconds' => env('MIN_TIMEOUT_SECONDS', 30),
];
