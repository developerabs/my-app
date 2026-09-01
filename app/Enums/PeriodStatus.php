<?php

namespace App\Enums;

enum PeriodStatus : string
{
    case UPCOMING = 'upcoming';
    case CURRENT = 'current';
    case CLOSED = 'closed';
}
