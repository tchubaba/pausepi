<?php

namespace App\Data\PiHole6API;

use Spatie\LaravelData\Data;

class Error extends Data
{
    public function __construct(
        public readonly string $key,
        public readonly string $message,
        public readonly ?string $hint
    ) {
    }
}
