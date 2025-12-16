<?php

declare(strict_types=1);

namespace Modules\Deputy\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Expense\Models\Expense;
use Modules\Shared\Traits\HasExternalId;
use Modules\Shared\Traits\HasSyncStatus;

final class Deputy extends Model
{
    use HasUuids;
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
        'birth_date' => 'date',
        'death_date' => 'date',
        'social_links' => 'array',
        'office' => 'array',
        'total_expenses' => 'decimal:2',
        'last_synced_at' => 'datetime',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeByName(Builder $query, string $name): Builder
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    public function scopeByState(Builder $query, string $state): Builder
    {
        return $query->where('state_code', $state);
    }

    public function scopeByParty(Builder $query, string $party): Builder
    {
        return $query->where('party_acronym', $party);
    }

    public function scopeInExercise(Builder $query): Builder
    {
        return $query->where('status', 'Exercício');
    }
}
