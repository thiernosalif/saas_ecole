<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Policies;

use App\Domain\Comptabilite\Models\Facture;
use App\Models\User;

class FacturePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'COMPTABLE', 'PARENT', 'ELEVE']);
    }

    public function view(User $user, Facture $facture): bool
    {
        if ($user->hasRole('PARENT')) {
            return $facture->eleve->parents->contains($user->personne);
        }

        if ($user->hasRole('ELEVE')) {
            return $user->personne_id === $facture->eleve_id;
        }

        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'COMPTABLE']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'COMPTABLE']);
    }
}
