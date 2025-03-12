<?php

namespace App\Data\PiHole6API;

use App\Enums\V6BlockingStatus;
use Spatie\LaravelData\Data;

class Blocking extends Data
{
    public function __construct(
        public readonly V6BlockingStatus $blocking,
        public readonly ?int $timer,
        public readonly float $took,
    ) {
    }
}
