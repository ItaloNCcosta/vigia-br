<?php

declare(strict_types=1);

namespace Modules\Expense\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Deputy\Models\Deputy;
use Modules\Shared\Traits\HasExternalId;
use OwenIt\Auditing\Contracts\Auditable;

final class Expense extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;
    use HasUuids;
    use HasExternalId;

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

    public function scopeByYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByMonth($query, int $month)
    {
        return $query->where('month', $month);
    }

    public function scopeByPeriod($query, ?string $start, ?string $end)
    {
        return $query
            ->when($start, fn($q) => $q->whereDate('document_date', '>=', $start))
            ->when($end, fn($q) => $q->whereDate('document_date', '<=', $end));
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('expense_type', $type);
    }
}
