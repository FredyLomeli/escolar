<?php

namespace App\Enums;

enum ModalityCode: string
{
    case Regular = 'regular';
    case Executive = 'executive';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Escolarizado',
            self::Executive => 'Ejecutivo',
        };
    }
}
