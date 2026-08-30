<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Services;

use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Support\Facades\DB;

class InscriptionService
{
    public function create(array $data): Inscription
    {
        // tenant_id n'est jamais accepté depuis l'appelant : seul le
        // TenantScope/BelongsToTenant (via currentTenantId) doit le fixer,
        // sinon un tenant_id injecté dans $data écraserait le tenant courant.
        unset($data['tenant_id']);

        return DB::transaction(fn () => Inscription::create([
            ...$data,
            'statut' => Inscription::STATUT_ACTIVE,
        ]));
    }
}
