<?php

namespace App\Enums;

enum AssetEntryType: string
{
    case OPENING = 'opening';
    case PURCHASE = 'purchase';
    case ADJUSTMENT = 'adjustment';
    case TRANSFER = 'transfer';
    case REVALUATION = 'revaluation';
    case DISPOSAL = 'disposal';
}
