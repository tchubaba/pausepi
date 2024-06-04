<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PiHoleBox;
use Exception;
use Illuminate\Support\Collection;
use Log;

class PiHoleBoxRepository
{
    /**
     * @var Collection<PiHoleBox>
     */
    protected Collection $piHoleBoxes;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $boxesConfig       = config('pihole.boxes');
        $this->piHoleBoxes = collect();

        foreach ($boxesConfig as $box) {
            try {
                $this->piHoleBoxes->add(new PiHoleBox($box['name'], $box['api_key'], $box['ip']));
            } catch (Exception $e) {
                Log::warning(
                    sprintf('Could not create pihole box: %s', $e->getMessage()),
                );
            }
        }

        if ($this->piHoleBoxes->isEmpty()) {
            throw new Exception(
                'Pihole boxes not properly configured! Ensure at least 1 Pihole box is configured correctly.'
            );
        }
    }

    public function getPiholeBoxes(): Collection
    {
        return $this->piHoleBoxes;
    }
}
