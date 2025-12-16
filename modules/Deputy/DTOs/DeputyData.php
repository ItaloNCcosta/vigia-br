<?php

declare(strict_types=1);

namespace Modules\Deputy\DTOs;

final readonly class DeputyData
{
    public function __construct(
        public int $externalId,
        public string $name,
        public ?string $civilName,
        public ?string $electoralName,
        public ?string $cpf,
        public ?string $gender,
        public ?string $birthDate,
        public ?string $birthCity,
        public ?string $birthState,
        public ?string $deathDate,
        public ?string $educationLevel,
        public string $stateCode,
        public string $partyAcronym,
        public ?string $status,
        public ?string $email,
        public ?string $photoUrl,
        public ?string $websiteUrl,
        public ?array $socialLinks,
        public ?string $uri,
        public ?array $office,
    ) {}

    public static function fromApi(array $data): self
    {
        $ultimoStatus = $data['ultimoStatus'] ?? [];
        $gabinete = $ultimoStatus['gabinete'] ?? [];

        return new self(
            externalId: (int) $data['id'],
            name: $ultimoStatus['nome'] ?? $data['nome'] ?? '',
            civilName: $data['nomeCivil'] ?? null,
            electoralName: $ultimoStatus['nomeEleitoral'] ?? null,
            cpf: $data['cpf'] ?? null,
            gender: $data['sexo'] ?? null,
            birthDate: $data['dataNascimento'] ?? null,
            birthCity: $data['municipioNascimento'] ?? null,
            birthState: $data['ufNascimento'] ?? null,
            deathDate: $data['dataFalecimento'] ?? null,
            educationLevel: $data['escolaridade'] ?? null,
            stateCode: $ultimoStatus['siglaUf'] ?? $data['siglaUf'] ?? '',
            partyAcronym: $ultimoStatus['siglaPartido'] ?? $data['siglaPartido'] ?? '',
            status: $ultimoStatus['situacao'] ?? null,
            email: $gabinete['email'] ?? $ultimoStatus['email'] ?? null,
            photoUrl: $ultimoStatus['urlFoto'] ?? $data['urlFoto'] ?? null,
            websiteUrl: $data['urlWebsite'] ?? null,
            socialLinks: $data['redeSocial'] ?? null,
            uri: $data['uri'] ?? null,
            office: !empty($gabinete) ? $gabinete : null,
        );
    }

    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'name' => $this->name,
            'civil_name' => $this->civilName,
            'electoral_name' => $this->electoralName,
            'cpf' => $this->cpf,
            'gender' => $this->gender,
            'birth_date' => $this->birthDate,
            'birth_city' => $this->birthCity,
            'birth_state' => $this->birthState,
            'death_date' => $this->deathDate,
            'education_level' => $this->educationLevel,
            'state_code' => $this->stateCode,
            'party_acronym' => $this->partyAcronym,
            'status' => $this->status,
            'email' => $this->email,
            'photo_url' => $this->photoUrl,
            'website_url' => $this->websiteUrl,
            'social_links' => $this->socialLinks,
            'uri' => $this->uri,
            'office' => $this->office,
            'last_synced_at' => now(),
        ];
    }
}