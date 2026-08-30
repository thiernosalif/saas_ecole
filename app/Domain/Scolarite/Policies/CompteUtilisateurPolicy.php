<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Policies;

use App\Models\User;

class CompteUtilisateurPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ECOLE_ADMIN');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ECOLE_ADMIN');
    }

    public function update(User $user, User $compte): bool
    {
        return $user->hasRole('ECOLE_ADMIN') && $user->tenant_id === $compte->tenant_id;
    }
}
