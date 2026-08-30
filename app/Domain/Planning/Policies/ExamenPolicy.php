<?php

declare(strict_types=1);

namespace App\Domain\Planning\Policies;

use App\Domain\Planning\Models\Examen;
use App\Models\User;

class ExamenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'PROF', 'PARENT', 'ELEVE']);
    }

    public function view(User $user, Examen $examen): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE']);
    }

    public function update(User $user, Examen $examen): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE']);
    }
}
