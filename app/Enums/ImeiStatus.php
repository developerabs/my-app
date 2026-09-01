<?php

namespace App\Enums;

enum ImeiStatus: string
{
    case AVAILABLE = 'available';
    case SOLD = 'sold';
    case RETURNED = 'returned';
    case DAMAGED = 'damaged';
}
