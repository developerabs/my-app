<?php

namespace App\Enums;

enum JournalVoucherStatus: string
{
    case DRAFT = 'draft';
    case POSTED = 'posted';
    case CANCELLED = 'cancelled';
    case REVERSED = 'reversed';
}
