<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Policies;

use App\Domain\Comptabilite\Models\Reglement;
use App\Models\User;

class ReglementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'PARENT', 'ELEVE']);
    }

    public function view(User $user, Reglement $reglement): bool
    {
        $eleve = $reglement->facture->eleve;

        if ($user->hasRole('PARENT')) {
            return $eleve->parents->contains($user->personne);
        }

        if ($user->hasRole('ELEVE')) {
            return $user->personne_id === $eleve->id;
        }

        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE']);
    }

    /**
     * Enregistrement manuel (espèces/chèque/virement) — réservé au staff,
     * cf. Session 11. Le paiement en ligne a sa propre ability, définie avec
     * PaiementService (Plan Mode).
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE']);
    }
}
