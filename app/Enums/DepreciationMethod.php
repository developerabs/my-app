<?php

namespace App\Enums;

enum DepreciationMethod: string
{
    case STRAIGHT_LINE = 'straight_line';
    case DECLINING_BALANCE = 'declining_balance';
    case UNIT_OF_PRODUCTION = 'unit_of_production';
    case MANUAL = 'manual';
}
