<?php

declare(strict_types=1);

namespace Modules\Deputy\Services;

use Illuminate\Support\Collection;
use Modules\Deputy\Models\Deputy;

final class GetExpenseYearsService
{
    public function execute(Deputy $deputy): Collection
    {
        return $deputy->expenses()
            ->selectRaw('DISTINCT year')
            ->orderByDesc('year')
            ->pluck('year');
    }
}
