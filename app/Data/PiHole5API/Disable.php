<?php

namespace App\Data\PiHole5API;

use Spatie\LaravelData\Data;

class Disable extends Data
{
    public function __construct(
        public readonly string $status,
    ) {
    }
}
