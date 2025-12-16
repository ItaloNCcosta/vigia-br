<?php

declare(strict_types=1);

namespace Modules\Shared\Traits;

trait HasExternalId
{
    public static function findByExternalId(int $externalId): ?static
    {
        return static::where('external_id', $externalId)->first();
    }
}
