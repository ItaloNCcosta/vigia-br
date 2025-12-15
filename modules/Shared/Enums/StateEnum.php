<?php

declare(strict_types=1);

namespace Modules\Shared\Enums;

enum StateEnum: string
{
    case AC = 'AC';
    case AL = 'AL';
    case AP = 'AP';
    case AM = 'AM';
    case BA = 'BA';
    case CE = 'CE';
    case DF = 'DF';
    case ES = 'ES';
    case GO = 'GO';
    case MA = 'MA';
    case MT = 'MT';
    case MS = 'MS';
    case MG = 'MG';
    case PA = 'PA';
    case PB = 'PB';
    case PR = 'PR';
    case PE = 'PE';
    case PI = 'PI';
    case RJ = 'RJ';
    case RN = 'RN';
    case RS = 'RS';
    case RO = 'RO';
    case RR = 'RR';
    case SC = 'SC';
    case SP = 'SP';
    case SE = 'SE';
    case TO = 'TO';

    /**
     * Retorna o nome completo do estado.
     */
    public function name(): string
    {
        return match ($this) {
            self::AC => 'Acre',
            self::AL => 'Alagoas',
            self::AP => 'Amapá',
            self::AM => 'Amazonas',
            self::BA => 'Bahia',
            self::CE => 'Ceará',
            self::DF => 'Distrito Federal',
            self::ES => 'Espírito Santo',
            self::GO => 'Goiás',
            self::MA => 'Maranhão',
            self::MT => 'Mato Grosso',
            self::MS => 'Mato Grosso do Sul',
            self::MG => 'Minas Gerais',
            self::PA => 'Pará',
            self::PB => 'Paraíba',
            self::PR => 'Paraná',
            self::PE => 'Pernambuco',
            self::PI => 'Piauí',
            self::RJ => 'Rio de Janeiro',
            self::RN => 'Rio Grande do Norte',
            self::RS => 'Rio Grande do Sul',
            self::RO => 'Rondônia',
            self::RR => 'Roraima',
            self::SC => 'Santa Catarina',
            self::SP => 'São Paulo',
            self::SE => 'Sergipe',
            self::TO => 'Tocantins',
        };
    }

    /**
     * Retorna a região do estado.
     */
    public function region(): string
    {
        return match ($this) {
            self::AC, self::AP, self::AM, self::PA, self::RO, self::RR, self::TO => 'Norte',
            self::AL, self::BA, self::CE, self::MA, self::PB, self::PE, self::PI, self::RN, self::SE => 'Nordeste',
            self::DF, self::GO, self::MT, self::MS => 'Centro-Oeste',
            self::ES, self::MG, self::RJ, self::SP => 'Sudeste',
            self::PR, self::RS, self::SC => 'Sul',
        };
    }

    /**
     * Retorna o número de deputados federais do estado.
     */
    public function deputySeats(): int
    {
        return match ($this) {
            self::SP => 70,
            self::MG => 53,
            self::RJ => 46,
            self::BA => 39,
            self::RS => 31,
            self::PR => 30,
            self::PE => 25,
            self::CE => 22,
            self::MA => 18,
            self::GO => 17,
            self::PA => 17,
            self::SC => 16,
            self::PB => 12,
            self::ES => 10,
            self::PI => 10,
            self::AL => 9,
            self::RN => 8,
            self::AM => 8,
            self::MT => 8,
            self::MS => 8,
            self::DF => 8,
            self::SE => 8,
            self::RO => 8,
            self::TO => 8,
            self::AC => 8,
            self::AP => 8,
            self::RR => 8,
        };
    }

    /**
     * Cria a partir do valor da API da Câmara.
     */
    public static function fromApi(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom(strtoupper($value));
    }

    /**
     * Retorna todos os estados como array para selects.
     *
     * @return array<string, string>
     */
    public static function toSelectArray(): array
    {
        $states = [];

        foreach (self::cases() as $state) {
            $states[$state->value] = $state->name();
        }

        asort($states);

        return $states;
    }

    /**
     * Retorna estados agrupados por região.
     *
     * @return array<string, array<string, string>>
     */
    public static function groupedByRegion(): array
    {
        $grouped = [];

        foreach (self::cases() as $state) {
            $region = $state->region();
            $grouped[$region][$state->value] = $state->name();
        }

        // Ordena regiões e estados
        ksort($grouped);
        foreach ($grouped as &$states) {
            asort($states);
        }

        return $grouped;
    }

    /**
     * Retorna estados de uma região específica.
     *
     * @param string $region
     * @return array<self>
     */
    public static function byRegion(string $region): array
    {
        return array_filter(
            self::cases(),
            fn (self $state) => $state->region() === $region
        );
    }
}
