<?php

declare(strict_types=1);

namespace App\Enums;

enum PauseResultStatus: string
{
    case SUCCESS          = 'success';
    case TIMEOUT          = 'timeout';
    case INVALID_RESPONSE = 'invalid_response';
}
