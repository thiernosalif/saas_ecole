<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Policies;

use App\Domain\Scolarite\Models\Personne;
use App\Models\User;

class ElevePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'PROF', 'SECRETAIRE']);
    }

    public function view(User $user, Personne $eleve): bool
    {
        if ($user->hasRole('PARENT')) {
            return $eleve->parents->contains($user->personne);
        }

        if ($user->hasRole('ELEVE')) {
            return $user->personne_id === $eleve->id;
        }

        // COMPTABLE n'a accès à Personne qu'en lecture d'un élève précis, jamais
        // viewAny/create/update : FactureController::index() n'a pas de "toutes
        // les factures" et passe par ElevePolicy::view (pas FacturePolicy) pour
        // vérifier qu'on peut voir CET élève avant de révéler ses factures.
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'PROF', 'SECRETAIRE', 'COMPTABLE']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'SECRETAIRE']);
    }

    public function update(User $user, Personne $eleve): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'SECRETAIRE']);
    }

    public function delete(User $user, Personne $eleve): bool
    {
        return $user->hasRole('ECOLE_ADMIN');
    }
}
