<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Policies;

use App\Domain\Scolarite\Models\Personne;
use App\Models\User;

class ElevePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'PROF']);
    }

    public function view(User $user, Personne $eleve): bool
    {
        if ($user->hasRole('PARENT')) {
            return $eleve->parents->contains($user->personne);
        }

        if ($user->hasRole('ELEVE')) {
            return $user->personne_id === $eleve->id;
        }

        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'PROF']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE']);
    }

    public function update(User $user, Personne $eleve): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE']);
    }

    public function delete(User $user, Personne $eleve): bool
    {
        return $user->hasRole('ECOLE_ADMIN');
    }
}
