<?php

namespace App\Enums;

enum DeliveryType: string
{
    case InPerson = 'in_person';
    case OnlineConnect = 'online_connect';
    case OnlineLive = 'online_live';
    case OnlineAsync = 'online_async';

    public function label(): string
    {
        return match ($this) {
            self::InPerson => 'Presencial',
            self::OnlineConnect => 'Clase Conecta',
            self::OnlineLive => 'Vivo Virtual (VV)',
            self::OnlineAsync => 'En línea asíncrona (@)',
        };
    }

    public function isOnline(): bool
    {
        return $this !== self::InPerson;
    }
}
