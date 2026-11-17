<?php

namespace App\Enums;

enum AvailabilityType: string
{
    case Available = 'available';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Unavailable => 'No disponible',
        };
    }
}
