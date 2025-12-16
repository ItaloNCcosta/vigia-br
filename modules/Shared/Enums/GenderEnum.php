<?php

declare(strict_types=1);

namespace Modules\Shared\Enums;

enum GenderEnum: string
{
    case M = 'M';
    case F = 'F';

    public function label(): string
    {
        return match ($this) {
            self::M => 'Masculino',
            self::F => 'Feminino',
        };
    }
}
