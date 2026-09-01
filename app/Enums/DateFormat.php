<?php

namespace App\Enums;

enum DateFormat: string
{
    case DMY_DASH = 'd-m-Y';
    case DMY_SLASH = 'd/m/Y';
    case DMY_DOT = 'd.m.Y';

    case MDY_DASH = 'm-d-Y';
    case MDY_SLASH = 'm/d/Y';
    case MDY_DOT = 'm.d.Y';

    case YMD_DASH = 'Y-m-d';
    case YMD_SLASH = 'Y/m/d';
    case YMD_DOT = 'Y.m.d';

    case D_M_SHORT = 'd M, Y';
    case D_M_LONG = 'd F, Y';

    case WEEKDAY_LONG = 'l, d F Y';
    case WEEKDAY_SHORT = 'D, d M Y';

    /**
     * Preview label
     */
    public function label(): string
    {
        return now()
            ->locale(app()->getLocale())
            ->translatedFormat($this->value);
    }

    /**
     * Format string
     */
    public function format(): string
    {
        return $this->value;
    }

    /**
     * Dropdown options
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [
                $case->value => $case->label(),
            ])
            ->toArray();
    }

    /**
     * Get enum from format
     */
    public static function fromFormat(string $format): ?self
    {
        return self::tryFrom($format);
    }

    /**
     * Validate format
     */
    public static function isValid(string $format): bool
    {
        return self::tryFrom($format) !== null;
    }
}