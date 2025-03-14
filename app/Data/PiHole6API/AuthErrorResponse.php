<?php

declare(strict_types=1);

namespace App\Data\PiHole6API;

use Spatie\LaravelData\Data;

class AuthErrorResponse extends Data
{
    public function __construct(
        public readonly Error $error,
        public readonly float $took,
    ) {
    }
}
