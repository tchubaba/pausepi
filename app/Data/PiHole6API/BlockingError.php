<?php

namespace App\Data\PiHole6API;

use Spatie\LaravelData\Data;

class BlockingError extends Data
{
    public function __construct(
        public readonly Error $error,
        public readonly float $took,
    ) {
    }
}
