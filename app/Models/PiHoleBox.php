<?php

declare(strict_types=1);

namespace App\Models;

readonly class PiHoleBox
{
    public string $name;

    public string $apiKey;

    public string $ipAddress;

    public function __construct(string $name, string $apiKey, string $ipAddress)
    {
        $this->name      = $name;
        $this->apiKey    = $apiKey;
        $this->ipAddress = $ipAddress;
    }
}
