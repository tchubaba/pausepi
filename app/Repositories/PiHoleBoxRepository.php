<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PiHoleBox;
use Illuminate\Support\Collection;

class PiHoleBoxRepository
{
    public function getPiholeBoxes(): Collection
    {
        return PiHoleBox::all();
    }

    public function getById(int $id): PiHoleBox|null
    {
        return PiHoleBox::firstWhere('id', $id);
    }
}
