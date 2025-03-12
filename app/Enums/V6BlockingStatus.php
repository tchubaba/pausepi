<?php

declare(strict_types=1);

namespace App\Enums;

enum V6BlockingStatus: string
{
    case ENABLED  = 'enabled';
    case DISABLED = 'disabled';
    case FAILED   = 'failed';
    case UNKNOWN  = 'unknown';
}
