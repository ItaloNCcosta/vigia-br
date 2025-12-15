<?php

declare(strict_types=1);

namespace Modules\Shared\Enums;

enum GenderEnum: string
{
    case MALE = 'M';
    case FEMALE = 'F';

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Masculino',
            self::FEMALE => 'Feminino',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::MALE => '♂️',
            self::FEMALE => '♀️',
        };
    }

    public static function fromApi(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return match (strtoupper($value)) {
            'M', 'MASCULINO' => self::MALE,
            'F', 'FEMININO' => self::FEMALE,
            default => null,
        };
    }

    public static function toSelectArray(): array
    {
        return [
            self::MALE->value => self::MALE->label(),
            self::FEMALE->value => self::FEMALE->label(),
        ];
    }
}
