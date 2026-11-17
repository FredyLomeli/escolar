<?php

namespace App\Domain\Scheduling;

enum FindingSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';

    public function label(): string
    {
        return match ($this) {
            self::Error => 'Error',
            self::Warning => 'Advertencia',
            self::Info => 'Información',
        };
    }
}
