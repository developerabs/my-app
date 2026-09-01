<?php

namespace App\Enums;

enum MeetingType: string
{
    case PHYSICAL = 'physical';
    case ONLINE = 'online';
    case PHONE = 'phone';

    public function label(): string
    {
        return match ($this) {
            self::PHYSICAL => 'Physical',
            self::ONLINE => 'Online',
            self::PHONE => 'Phone',
        };
    }
}
