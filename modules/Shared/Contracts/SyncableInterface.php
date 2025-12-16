<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts;

interface SyncableInterface
{
    public static function upsertFromApi(array $data): static;
}
