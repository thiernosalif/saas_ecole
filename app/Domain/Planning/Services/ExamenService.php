<?php

declare(strict_types=1);

namespace App\Domain\Planning\Services;

use App\Domain\Planning\Models\Examen;
use Illuminate\Support\Facades\DB;

class ExamenService
{
    public function create(array $data): Examen
    {
        // tenant_id n'est jamais accepté depuis l'appelant : seul le
        // TenantScope/BelongsToTenant (via currentTenantId) doit le fixer,
        // sinon un tenant_id injecté dans $data écraserait le tenant courant.
        unset($data['tenant_id']);

        return DB::transaction(fn () => Examen::create($data));
    }

    public function update(Examen $examen, array $data): Examen
    {
        unset($data['tenant_id']);

        return DB::transaction(function () use ($examen, $data) {
            $examen->update($data);

            return $examen->refresh();
        });
    }
}
