<?php

declare(strict_types=1);

namespace App\Domain\Planning\Services;

use App\Domain\Planning\Models\Seance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SeanceService
{
    public function create(array $data): Seance
    {
        // tenant_id n'est jamais accepté depuis l'appelant : seul le
        // TenantScope/BelongsToTenant (via currentTenantId) doit le fixer,
        // sinon un tenant_id injecté dans $data écraserait le tenant courant.
        unset($data['tenant_id']);

        $this->verifierChevauchement($data);

        return DB::transaction(fn () => Seance::create($data));
    }

    public function deplacer(Seance $seance, array $data): Seance
    {
        unset($data['tenant_id']);

        $this->verifierChevauchement([...$seance->only([
            'jour_semaine', 'heure_debut', 'heure_fin', 'salle_id', 'prof_id',
        ]), ...$data], excludeSeanceId: $seance->id);

        return DB::transaction(function () use ($seance, $data) {
            $seance->update($data);

            return $seance->refresh();
        });
    }

    /**
     * Une salle ou un prof ne peuvent pas être affectés à deux séances qui se
     * chevauchent le même jour de la semaine.
     */
    private function verifierChevauchement(array $data, ?string $excludeSeanceId = null): void
    {
        $chevauche = fn ($query) => $query
            ->where('jour_semaine', $data['jour_semaine'])
            ->where('heure_debut', '<', $data['heure_fin'])
            ->where('heure_fin', '>', $data['heure_debut'])
            ->when($excludeSeanceId, fn ($q) => $q->where('id', '!=', $excludeSeanceId));

        if (! empty($data['salle_id'])
            && $chevauche(Seance::where('salle_id', $data['salle_id']))->exists()) {
            throw ValidationException::withMessages([
                'salle_id' => ['Cette salle est déjà occupée sur ce créneau.'],
            ]);
        }

        if (! empty($data['prof_id'])
            && $chevauche(Seance::where('prof_id', $data['prof_id']))->exists()) {
            throw ValidationException::withMessages([
                'prof_id' => ['Ce professeur a déjà une séance sur ce créneau.'],
            ]);
        }
    }
}
