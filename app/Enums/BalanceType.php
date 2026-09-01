<?php

namespace App\Enums;

enum BalanceType : string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';
}
