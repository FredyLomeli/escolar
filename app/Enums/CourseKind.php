<?php

namespace App\Enums;

enum CourseKind: string
{
    case Regular = 'regular';
    case Additional = 'additional';
    case Stay = 'stay';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular',
            self::Additional => 'Adicional',
            self::Stay => 'Estadía empresarial',
        };
    }
}
