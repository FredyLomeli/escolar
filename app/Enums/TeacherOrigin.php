<?php

namespace App\Enums;

enum TeacherOrigin: string
{
    case Campus = 'campus';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Campus => 'Sede',
            self::External => 'Externo',
        };
    }
}
