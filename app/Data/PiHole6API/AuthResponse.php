<?php

declare(strict_types=1);

namespace App\Data\PiHole6API;

use Spatie\LaravelData\Data;

class AuthResponse extends Data
{
    public function __construct(
        public readonly Session $session,
        public readonly float $took,
    ) {
    }

    public function sessionIsValid(): bool
    {
        return $this->session->valid;
    }
}
