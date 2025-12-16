<?php

declare(strict_types=1);

namespace Modules\Deputy\Services;

use Illuminate\Support\Collection;
use Modules\Deputy\Models\Deputy;

final class GetPartyOptionsService
{
    public function execute(): Collection
    {
        return Deputy::query()
            ->selectRaw('DISTINCT party_acronym as value, party_acronym as name')
            ->orderBy('party_acronym')
            ->get();
    }
}
