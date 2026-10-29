<?php

declare(strict_types=1);

namespace App\Enum\Type;

enum PreviewStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Unavailable = 'unavailable';
}
