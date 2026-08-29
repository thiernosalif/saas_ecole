<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Policies;

use App\Domain\Scolarite\Models\Classe;
use App\Models\User;

class ClassePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'PROF', 'PARENT', 'ELEVE']);
    }

    public function view(User $user, Classe $classe): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE']);
    }

    public function update(User $user, Classe $classe): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE']);
    }

    public function delete(User $user, Classe $classe): bool
    {
        return $user->hasRole('ECOLE_ADMIN');
    }
}
