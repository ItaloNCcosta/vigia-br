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
}
