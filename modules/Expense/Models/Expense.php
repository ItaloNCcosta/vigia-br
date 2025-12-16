<?php

declare(strict_types=1);

namespace Modules\Expense\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Deputy\Models\Deputy;
use Modules\Shared\Traits\HasExternalId;
use Modules\Shared\Traits\HasSyncStatus;

final class Expense extends Model
{
    use HasUuids;
    use HasExternalId;
    use HasSyncStatus;

    protected $table = 'expenses';

    protected $fillable = [
        'deputy_id',
        'external_id',
        'year',
        'month',
        'expense_type',
        'document_type',
        'document_type_code',
        'document_number',
        'document_date',
        'document_url',
        'document_value',
        'net_value',
        'disallowed_value',
        'supplier_name',
        'supplier_document',
        'reimbursement_number',
        'batch_code',
        'installment',
        'last_synced_at',
    ];

    protected $casts = [
        'external_id' => 'integer',
        'year' => 'integer',
        'month' => 'integer',
        'document_type_code' => 'integer',
        'document_date' => 'date',
        'document_value' => 'decimal:2',
        'net_value' => 'decimal:2',
        'disallowed_value' => 'decimal:2',
        'batch_code' => 'integer',
        'installment' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function deputy(): BelongsTo
    {
        return $this->belongsTo(Deputy::class);
    }

    public function scopeByYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    public function scopeByMonth(Builder $query, int $month): Builder
    {
        return $query->where('month', $month);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('expense_type', $type);
    }

    public function scopeBySupplier(Builder $query, string $supplier): Builder
    {
        return $query->where('supplier_name', 'like', "%{$supplier}%");
    }

    public function scopeByPeriod(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('document_date', [$start, $end]);
    }
}