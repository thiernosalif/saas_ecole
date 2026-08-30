<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Services;

use App\Domain\Scolarite\Models\Personne;
use Illuminate\Support\Facades\DB;

class EleveService
{
    public function create(array $data): Personne
    {
        // tenant_id n'est jamais accepté depuis l'appelant : seul le
        // TenantScope/BelongsToTenant (via currentTenantId) doit le fixer,
        // sinon un tenant_id injecté dans $data écraserait le tenant courant.
        unset($data['tenant_id']);

        return DB::transaction(fn () => Personne::create([
            ...$data,
            'type' => Personne::TYPE_ELEVE,
        ]));
    }

    public function update(Personne $eleve, array $data): Personne
    {
        unset($data['tenant_id']);

        DB::transaction(function () use ($eleve, $data) {
            $eleve->update($data);
        });

        return $eleve->refresh();
    }

    public function delete(Personne $eleve): void
    {
        DB::transaction(fn () => $eleve->delete());
    }
}
