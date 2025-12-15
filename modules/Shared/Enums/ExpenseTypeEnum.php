<?php

declare(strict_types=1);

namespace Modules\Shared\Enums;

enum ExpenseTypeEnum: string
{
    case OFFICE_MAINTENANCE = 'MANUTENÇÃO DE ESCRITÓRIO DE APOIO À ATIVIDADE PARLAMENTAR';
    case FUEL = 'COMBUSTÍVEIS E LUBRIFICANTES';
    case CONSULTANCY = 'CONSULTORIAS, PESQUISAS E TRABALHOS TÉCNICOS';
    case PUBLICITY = 'DIVULGAÇÃO DA ATIVIDADE PARLAMENTAR';
    case FOOD = 'FORNECIMENTO DE ALIMENTAÇÃO DO PARLAMENTAR';
    case LODGING = 'HOSPEDAGEM, EXCETO DO PARLAMENTAR NO DISTRITO FEDERAL';
    case AIRLINE_TICKETS = 'PASSAGENS AÉREAS';
    case VEHICLE_RENTAL = 'LOCAÇÃO OU FRETAMENTO DE VEÍCULOS AUTOMOTORES';
    case AIRCRAFT_RENTAL = 'LOCAÇÃO OU FRETAMENTO DE AERONAVES';
    case WATERCRAFT_RENTAL = 'LOCAÇÃO OU FRETAMENTO DE EMBARCAÇÕES';
    case BOAT_RENTAL = 'LOCAÇÃO OU FRETAMENTO DE EMBARCAÇÕES'; // Alias
    case SECURITY = 'SERVIÇO DE SEGURANÇA PRESTADO POR EMPRESA ESPECIALIZADA';
    case TAXI = 'SERVIÇO DE TÁXI, PEDÁGIO E ESTACIONAMENTO';
    case TELEPHONY = 'TELEFONIA';
    case POSTAL = 'SERVIÇOS POSTAIS';
    case SOFTWARE = 'AQUISIÇÃO OU LOCAÇÃO DE SOFTWARE; SERVIÇOS POSTAIS; ASSINATURAS';
    case SUBSCRIPTIONS = 'ASSINATURA DE PUBLICAÇÕES';
    case COURSES = 'PARTICIPAÇÃO EM CURSO, PALESTRA, SEMINÁRIO, SIMPÓSIO, CONGRESSO OU EVENTO';

    /**
     * Retorna o label simplificado.
     */
    public function label(): string
    {
        return match ($this) {
            self::OFFICE_MAINTENANCE => 'Escritório',
            self::FUEL => 'Combustível',
            self::CONSULTANCY => 'Consultoria',
            self::PUBLICITY => 'Divulgação',
            self::FOOD => 'Alimentação',
            self::LODGING => 'Hospedagem',
            self::AIRLINE_TICKETS => 'Passagens Aéreas',
            self::VEHICLE_RENTAL => 'Locação de Veículos',
            self::AIRCRAFT_RENTAL => 'Locação de Aeronaves',
            self::WATERCRAFT_RENTAL, self::BOAT_RENTAL => 'Locação de Embarcações',
            self::SECURITY => 'Segurança',
            self::TAXI => 'Táxi/Pedágio',
            self::TELEPHONY => 'Telefonia',
            self::POSTAL => 'Correios',
            self::SOFTWARE => 'Software',
            self::SUBSCRIPTIONS => 'Assinaturas',
            self::COURSES => 'Cursos/Eventos',
        };
    }

    /**
     * Retorna o ícone (emoji).
     */
    public function icon(): string
    {
        return match ($this) {
            self::OFFICE_MAINTENANCE => '🏢',
            self::FUEL => '⛽',
            self::CONSULTANCY => '📊',
            self::PUBLICITY => '📣',
            self::FOOD => '🍽️',
            self::LODGING => '🏨',
            self::AIRLINE_TICKETS => '✈️',
            self::VEHICLE_RENTAL => '🚗',
            self::AIRCRAFT_RENTAL => '🛩️',
            self::WATERCRAFT_RENTAL, self::BOAT_RENTAL => '🚤',
            self::SECURITY => '🔒',
            self::TAXI => '🚕',
            self::TELEPHONY => '📱',
            self::POSTAL => '📮',
            self::SOFTWARE => '💻',
            self::SUBSCRIPTIONS => '📰',
            self::COURSES => '🎓',
        };
    }

    /**
     * Retorna a categoria agrupada.
     */
    public function category(): string
    {
        return match ($this) {
            self::AIRLINE_TICKETS, self::VEHICLE_RENTAL, self::AIRCRAFT_RENTAL,
            self::WATERCRAFT_RENTAL, self::BOAT_RENTAL, self::TAXI, self::FUEL => 'Transporte',

            self::FOOD, self::LODGING => 'Viagem',

            self::OFFICE_MAINTENANCE, self::TELEPHONY, self::POSTAL,
            self::SOFTWARE, self::SUBSCRIPTIONS => 'Escritório',

            self::CONSULTANCY, self::PUBLICITY, self::COURSES => 'Serviços',

            self::SECURITY => 'Segurança',
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

        // Normaliza o texto (remove acentos extras, espaços, etc.)
        $normalized = mb_strtoupper(trim($value));

        // Tenta match direto primeiro
        $case = self::tryFrom($normalized);
        if ($case !== null) {
            return $case;
        }

        // Tenta match parcial para variações
        foreach (self::cases() as $case) {
            if (str_contains($normalized, mb_strtoupper(substr($case->value, 0, 30)))) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Retorna todos os tipos como array para selects.
     *
     * @return array<string, string>
     */
    public static function toSelectArray(): array
    {
        $types = [];

        foreach (self::cases() as $type) {
            $types[$type->value] = $type->label();
        }

        asort($types);

        return $types;
    }

    /**
     * Retorna tipos agrupados por categoria.
     *
     * @return array<string, array<string, string>>
     */
    public static function groupedByCategory(): array
    {
        $grouped = [];

        foreach (self::cases() as $type) {
            $category = $type->category();
            $grouped[$category][$type->value] = $type->label();
        }

        ksort($grouped);

        return $grouped;
    }
}
