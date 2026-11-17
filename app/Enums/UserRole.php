<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Coordinator = 'coordinator';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Coordinator => 'Coordinador',
        };
    }
}
