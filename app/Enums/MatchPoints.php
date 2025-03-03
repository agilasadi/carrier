<?php

declare(strict_types=1);

namespace App\Enums;

enum MatchPoints: int
{
    case WIN = 3;
    case DRAW = 1;
    case LOSE = 0;
} 