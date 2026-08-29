<?php

declare(strict_types=1);

namespace App\Domain\Notes\Policies;

use App\Domain\Notes\Models\Bulletin;
use App\Models\User;

class BulletinPolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE']);
    }

    public function view(User $user, Bulletin $bulletin): bool
    {
        if ($user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'PROF'])) {
            return true;
        }

        if ($user->hasRole('ELEVE')) {
            return $bulletin->eleve_id === $user->personne_id;
        }

        if ($user->hasRole('PARENT')) {
            return $bulletin->eleve->parents->contains('id', $user->personne_id);
        }

        return false;
    }
}
