<?php

declare(strict_types=1);

namespace Modules\Deputy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Expense\Models\Expense;
use Modules\Legislature\Models\Legislature;
use Modules\Party\Models\Party;
use Modules\Shared\Enums\GenderEnum;
use Modules\Shared\Enums\StateEnum;
use Modules\Shared\Traits\HasExternalId;
use Modules\Shared\Traits\HasSyncStatus;

final class Deputy extends Model
{
    use HasUuids;
    use HasFactory;
    use HasExternalId;
    use HasSyncStatus;

    protected $table = 'deputies';

    protected $fillable = [
        'external_id',
        'legislature_id',
        'party_id',
        'name',
        'civil_name',
        'electoral_name',
        'cpf',
        'gender',
        'birth_date',
        'birth_city',
        'birth_state',
        'death_date',
        'education_level',
        'state_code',
        'party_acronym',
        'status',
        'email',
        'photo_url',
        'website_url',
        'social_links',
        'uri',
        'office',
        'total_expenses',
        'last_synced_at',
    ];

    protected $casts = [
        'external_id' => 'integer',
        'gender' => GenderEnum::class,
        'birth_date' => 'date',
        'death_date' => 'date',
        'social_links' => 'array',
        'office' => 'array',
        'total_expenses' => 'decimal:2',
        'last_synced_at' => 'datetime',
    ];

    protected $attributes = [
        'total_expenses' => '0.00',
    ];

    public function legislature(): BelongsTo
    {
        return $this->belongsTo(Legislature::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeByState(Builder $query, string|StateEnum $state): Builder
    {
        $code = $state instanceof StateEnum ? $state->value : $state;

        return $query->where('state_code', $code);
    }

    public function scopeByParty(Builder $query, string $acronym): Builder
    {
        return $query->where('party_acronym', $acronym);
    }

    public function scopeByName(Builder $query, string $name): Builder
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    public function scopeInExercise(Builder $query): Builder
    {
        return $query->where('status', 'Exercício');
    }

    public function scopeAlive(Builder $query): Builder
    {
        return $query->whereNull('death_date');
    }

    public function scopeTopSpenders(Builder $query): Builder
    {
        return $query->orderByDesc('total_expenses');
    }

    public function scopeWithExpensesSum(Builder $query): Builder
    {
        return $query->withSum('expenses', 'net_value');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->electoral_name ?? $this->name;
    }

    public function getStateEnumAttribute(): ?StateEnum
    {
        return StateEnum::tryFrom($this->state_code);
    }

    public function getStateNameAttribute(): ?string
    {
        return $this->state_enum?->name();
    }

    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }

        $endDate = $this->death_date ?? now();

        return $this->birth_date->diffInYears($endDate);
    }

    public function getOfficePhoneAttribute(): ?string
    {
        return $this->office['telefone'] ?? null;
    }

    public function getOfficeEmailAttribute(): ?string
    {
        return $this->office['email'] ?? $this->email;
    }

    public function getOfficeLocationAttribute(): ?string
    {
        if (!$this->office) {
            return null;
        }

        $parts = array_filter([
            isset($this->office['predio']) ? "Prédio {$this->office['predio']}" : null,
            isset($this->office['andar']) ? "Andar {$this->office['andar']}" : null,
            isset($this->office['sala']) ? "Sala {$this->office['sala']}" : null,
        ]);

        return implode(', ', $parts) ?: null;
    }

    public function updateTotalExpenses(): void
    {
        $total = $this->expenses()->sum('net_value');

        $this->update(['total_expenses' => $total]);
    }

    public function recalculateTotalExpenses(): string
    {
        $this->updateTotalExpenses();

        return $this->fresh()->total_expenses;
    }

    public function isInExercise(): bool
    {
        return $this->status === 'Exercício';
    }

    public function isDeceased(): bool
    {
        return $this->death_date !== null;
    }

    public function getSocialLinksCollection(): Collection
    {
        return collect($this->social_links ?? []);
    }

    public function getSocialLink(string $network): ?string
    {
        $network = strtolower($network);

        return $this->getSocialLinksCollection()
            ->first(fn ($link) => str_contains(strtolower($link), $network));
    }

    public static function upsertFromApi(int $externalId, array $apiData): static
    {
        return static::upsertByExternalId($externalId, [
            'name' => $apiData['nome'] ?? $apiData['ultimoStatus']['nome'] ?? '',
            'civil_name' => $apiData['nomeCivil'] ?? null,
            'electoral_name' => $apiData['ultimoStatus']['nomeEleitoral'] ?? null,
            'cpf' => $apiData['cpf'] ?? null,
            'gender' => GenderEnum::fromApi($apiData['sexo'] ?? null),
            'birth_date' => $apiData['dataNascimento'] ?? null,
            'birth_city' => $apiData['municipioNascimento'] ?? null,
            'birth_state' => $apiData['ufNascimento'] ?? null,
            'death_date' => $apiData['dataFalecimento'] ?? null,
            'education_level' => $apiData['escolaridade'] ?? null,
            'state_code' => $apiData['ultimoStatus']['siglaUf'] ?? $apiData['siglaUf'] ?? '',
            'party_acronym' => $apiData['ultimoStatus']['siglaPartido'] ?? $apiData['siglaPartido'] ?? '',
            'status' => $apiData['ultimoStatus']['situacao'] ?? null,
            'email' => $apiData['ultimoStatus']['gabinete']['email'] ?? $apiData['ultimoStatus']['email'] ?? null,
            'photo_url' => $apiData['ultimoStatus']['urlFoto'] ?? $apiData['urlFoto'] ?? null,
            'website_url' => $apiData['urlWebsite'] ?? null,
            'social_links' => $apiData['redeSocial'] ?? null,
            'uri' => $apiData['uri'] ?? null,
            'office' => $apiData['ultimoStatus']['gabinete'] ?? null,
            'last_synced_at' => now(),
        ]);
    }
}
