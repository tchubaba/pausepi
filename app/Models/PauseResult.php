<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PauseResultStatus;
use Carbon\Carbon;

readonly class PauseResult
{
    public PiHoleBox $piholeBox;

    public PauseResultStatus $status;

    public Carbon $timestamp;

    public function __construct(
        PiHoleBox $piholeBox,
        PauseResultStatus $pauseResultStatus,
        Carbon $timestamp,
    ) {
        $this->piholeBox  = $piholeBox;
        $this->status     = $pauseResultStatus;
        $this->timestamp  = $timestamp;
    }
}
