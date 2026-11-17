<?php

namespace App\Enums;

enum TeacherCourseSource: string
{
    case Manual = 'manual';
    case History = 'history';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::History => 'Historial',
        };
    }
}
