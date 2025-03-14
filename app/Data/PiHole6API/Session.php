<?php

declare(strict_types=1);

namespace App\Data\PiHole6API;

use Spatie\LaravelData\Data;

class Session extends Data
{
    public function __construct(
        public readonly bool $valid,
        public readonly bool $totp,
        public readonly ?string $sid,
        public readonly ?string $csrf,
        public readonly int $validity,
        public readonly ?string $message
    ) {
    }
}
