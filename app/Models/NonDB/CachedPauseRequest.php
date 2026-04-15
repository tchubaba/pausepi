<?php

declare(strict_types=1);

namespace App\Models\NonDB;

use Carbon\Carbon;
use Illuminate\Support\Collection;

readonly class CachedPauseRequest
{
    public const string CACHE_KEY = 'piholepauser:pause';

    public int $seconds;

    public Carbon $date;

    /**
     * @var Collection<int, PauseResult>
     */
    public Collection $report;

    public int $totalSeconds;

    public function __construct(
        int $seconds,
        Carbon $date,
        Collection $report,
        int $totalSeconds = 0,
    ) {
        $this->seconds      = $seconds;
        $this->date         = $date;
        $this->report       = $report;
        $this->totalSeconds = $totalSeconds;
    }
}
