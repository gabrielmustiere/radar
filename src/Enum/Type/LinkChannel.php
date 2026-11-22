<?php

declare(strict_types=1);

namespace App\Enum\Type;

/** Par où un lien est entré dans Radar : lu en volumes agrégés, jamais affiché (R9). */
enum LinkChannel: string
{
    case Web = 'web';
    case Extension = 'extension';
    case Share = 'share';
}
