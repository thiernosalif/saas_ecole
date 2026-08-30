<?php

declare(strict_types=1);

namespace App\Domain\Planning\Services;

use App\Domain\Planning\Models\Devoir;
use Illuminate\Support\Facades\DB;

class DevoirService
{
    public function create(array $data): Devoir
    {
        // tenant_id n'est jamais accepté depuis l'appelant : seul le
        // TenantScope/BelongsToTenant (via currentTenantId) doit le fixer,
        // sinon un tenant_id injecté dans $data écraserait le tenant courant.
        unset($data['tenant_id']);

        return DB::transaction(fn () => Devoir::create($data));
    }

    public function update(Devoir $devoir, array $data): Devoir
    {
        unset($data['tenant_id']);

        return DB::transaction(function () use ($devoir, $data) {
            $devoir->update($data);

            return $devoir->refresh();
        });
    }
}
