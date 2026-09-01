<?php

namespace App\Enums;

enum GeneralLedgerStatus: string
{
    case POSTED = 'posted';
    case REVERSED = 'reversed';
}
