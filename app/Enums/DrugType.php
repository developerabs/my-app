<?php

namespace App\Enums;

enum DrugType: string
{
    case TABLET = 'tablet';
    case CAPSULE = 'capsule';
    case SYRUP = 'syrup';
    case INJECTION = 'injection';
    case OINTMENT = 'ointment';
    case DROPS = 'drops';
    case SUPPOSITORIE = 'suppositorie';
    case INHALER = 'inhaler';
    case SUSPENSION = 'suspension';
    case GEL = 'gel';

    public function shortName(): string {
        return match($this) {
            self::TABLET => 'Tab',
            self::CAPSULE => 'Cap',
            self::SYRUP => 'Syr',
            self::INJECTION => 'Inj',
            self::OINTMENT => 'Oint',
            self::DROPS => 'Drp',
            self::SUPPOSITORIE => 'Supp',
            self::INHALER => 'Inh',
            self::SUSPENSION => 'Susp',
            self::GEL => 'Gel',
        };
    }
}
