<?php

namespace App\Enums;

enum AcademicPeriodStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'En planeación',
            self::Published => 'Publicado',
            self::Closed => 'Cerrado',
        };
    }
}
