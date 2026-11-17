<?php

namespace App\Enums;

enum StudentGroupStatus: string
{
    case Active = 'active';
    case Graduated = 'graduated';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Graduated => 'Egresado',
            self::Inactive => 'Inactivo',
        };
    }
}
