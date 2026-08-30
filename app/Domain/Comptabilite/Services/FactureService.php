<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Services;

use App\Domain\Comptabilite\Models\Facture;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FactureService
{
    public function create(array $data): Facture
    {
        // tenant_id n'est jamais accepté depuis l'appelant : seul le
        // TenantScope/BelongsToTenant (via currentTenantId) doit le fixer,
        // sinon un tenant_id injecté dans $data écraserait le tenant courant.
        unset($data['tenant_id']);

        return DB::transaction(fn () => Facture::create([
            ...$data,
            'montant_paye' => 0,
            'statut' => Facture::STATUT_EN_ATTENTE,
        ]));
    }

    public function pourEleve(string $eleveId): Collection
    {
        return Facture::where('eleve_id', $eleveId)
            ->with('reglements')
            ->latest('created_at')
            ->get();
    }
}
