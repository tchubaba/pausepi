<?php

declare(strict_types=1);

namespace App\Enums;

enum PiHoleAPIClientPromiseResultState: string
{
    case FULFILLED = 'fulfilled';
    case REJECTED  = 'rejected';
}
