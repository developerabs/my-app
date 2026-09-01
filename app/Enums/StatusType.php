<?php

namespace App\Enums;

enum StatusType : string
{
    case LEAD = 'lead';
    case DEAL = 'deal';
    case MEETING = 'meeting';
}
