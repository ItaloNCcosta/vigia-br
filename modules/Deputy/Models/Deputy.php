<?php

declare(strict_types=1);

namespace Modules\Deputy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Modules\Expense\Models\Expense;
use Modules\Legislature\Models\Legislature;
use Modules\Party\Models\Party;
use Modules\Shared\Enums\GenderEnum;
use Modules\Shared\Enums\StateEnum;
use Modules\Shared\Traits\HasExternalId;
use Modules\Shared\Traits\HasSyncStatus;

/**
 * @property string $id
 * @property int $external_id
 * @property string|null $legislature_id
 * @property string|null $party_id
 * @property string $name
 * @property string|null $civil_name
 * @property string|null $electoral_name
 * @property string|null $cpf
 * @property GenderEnum|null $gender
 * @property \Carbon\Carbon|null $birth_date
 * @property string|null $birth_city
 * @property string|null $birth_state
 * @property \Carbon\Carbon|null $death_date
 * @property string|null $education_level
 * @property string $state_code
 * @property string $party_acronym
 * @property string|null $status
 * @property string|null $email
 * @property string|null $photo_url
 * @property string|null $website_url
 * @property array|null $social_links
 * @property string|null $uri
 * @property array|null $office
 * @property string $total_expenses
 * @property \Carbon\Carbon|null $last_synced_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Legislature|null $legislature
 * @property-read Party|null $party
 * @property-read \Illuminate\Database\Eloquent\Collection<Expense> $expenses
 */
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

    /* ========================================
     * Relationships
     * ======================================== */

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

    /* ========================================
     * Scopes
     * ======================================== */

    /**
     * Filtra por estado.
     */
    public function scopeByState(Builder $query, string|StateEnum $state): Builder
    {
        $code = $state instanceof StateEnum ? $state->value : $state;

        return $query->where('state_code', $code);
    }

    /**
     * Filtra por partido.
     */
    public function scopeByParty(Builder $query, string $acronym): Builder
    {
        return $query->where('party_acronym', $acronym);
    }

    /**
     * Filtra por nome (busca parcial).
     */
    public function scopeByName(Builder $query, string $name): Builder
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Apenas deputados em exercício.
     */
    public function scopeInExercise(Builder $query): Builder
    {
        return $query->where('status', 'Exercício');
    }

    /**
     * Apenas deputados ativos (não falecidos).
     */
    public function scopeAlive(Builder $query): Builder
    {
        return $query->whereNull('death_date');
    }

    /**
     * Ordenado por total de despesas (maiores primeiro).
     */
    public function scopeTopSpenders(Builder $query): Builder
    {
        return $query->orderByDesc('total_expenses');
    }

    /**
     * Com soma de despesas calculada.
     */
    public function scopeWithExpensesSum(Builder $query): Builder
    {
        return $query->withSum('expenses', 'net_value');
    }

    /* ========================================
     * Accessors & Mutators
     * ======================================== */

    /**
     * Retorna o nome formatado para exibição.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->electoral_name ?? $this->name;
    }

    /**
     * Retorna estado como Enum.
     */
    public function getStateEnumAttribute(): ?StateEnum
    {
        return StateEnum::tryFrom($this->state_code);
    }

    /**
     * Retorna o nome completo do estado.
     */
    public function getStateNameAttribute(): ?string
    {
        return $this->state_enum?->name();
    }

    /**
     * Retorna a idade do deputado.
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }

        $endDate = $this->death_date ?? now();

        return $this->birth_date->diffInYears($endDate);
    }

    /**
     * Retorna o telefone do gabinete formatado.
     */
    public function getOfficePhoneAttribute(): ?string
    {
        return $this->office['telefone'] ?? null;
    }

    /**
     * Retorna o email do gabinete.
     */
    public function getOfficeEmailAttribute(): ?string
    {
        return $this->office['email'] ?? $this->email;
    }

    /**
     * Retorna a localização completa do gabinete.
     */
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

    /* ========================================
     * Business Methods
     * ======================================== */

    /**
     * Atualiza o total de despesas do deputado.
     */
    public function updateTotalExpenses(): void
    {
        $total = $this->expenses()->sum('net_value');

        $this->update(['total_expenses' => $total]);
    }

    /**
     * Recalcula e atualiza total de despesas.
     */
    public function recalculateTotalExpenses(): string
    {
        $this->updateTotalExpenses();

        return $this->fresh()->total_expenses;
    }

    /**
     * Verifica se o deputado está em exercício.
     */
    public function isInExercise(): bool
    {
        return $this->status === 'Exercício';
    }

    /**
     * Verifica se o deputado faleceu.
     */
    public function isDeceased(): bool
    {
        return $this->death_date !== null;
    }

    /**
     * Retorna as redes sociais como collection.
     */
    public function getSocialLinksCollection(): \Illuminate\Support\Collection
    {
        return collect($this->social_links ?? []);
    }

    /**
     * Retorna link de uma rede social específica.
     */
    public function getSocialLink(string $network): ?string
    {
        $network = strtolower($network);

        return $this->getSocialLinksCollection()
            ->first(fn ($link) => str_contains(strtolower($link), $network));
    }

    /**
     * Cria ou atualiza deputado a partir dos dados da API.
     *
     * @param array<string, mixed> $apiData
     */
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
