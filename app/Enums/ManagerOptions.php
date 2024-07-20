<?php

declare(strict_types=1);

namespace App\Enums;

enum ManagerOptions: string
{
    case VIEW   = 'View Pi-holes';
    case ADD    = 'Add Pi-hole';
    case EDIT   = 'Edit Pi-holes';
    case REMOVE = 'Remove Pi-holes';
    case EXIT   = 'Exit';
}
