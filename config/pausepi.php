<?php

declare(strict_types=1);

return [
    'min_timeout_seconds' => env('PAUSEPI_MIN_TIMEOUT_SECONDS', 30),
    'max_timeout_seconds' => env('PAUSEPI_MAX_TIMEOUT_SECONDS', 300),
];
