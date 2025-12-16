<?php

declare(strict_types=1);

namespace Modules\Deputy\Services;

use Modules\Deputy\Models\Deputy;

final class FindDeputyService
{
    public function execute(string $id): ?Deputy
    {
        return Deputy::find($id);
    }
}