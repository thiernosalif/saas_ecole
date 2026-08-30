<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Services;

use App\Domain\Scolarite\Models\Absence;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AbsenceService
{
    public function create(array $data, ?string $saisiePar): Absence
    {
        // tenant_id n'est jamais accepté depuis l'appelant : seul le
        // TenantScope/BelongsToTenant (via currentTenantId) doit le fixer,
        // sinon un tenant_id injecté dans $data écraserait le tenant courant.
        unset($data['tenant_id']);

        return DB::transaction(fn () => Absence::create([
            ...$data,
            'saisie_par' => $data['saisie_par'] ?? $saisiePar,
        ]));
    }

    public function pourEleve(string $eleveId): Collection
    {
        return Absence::where('eleve_id', $eleveId)
            ->orderByDesc('date')
            ->get();
    }
}
