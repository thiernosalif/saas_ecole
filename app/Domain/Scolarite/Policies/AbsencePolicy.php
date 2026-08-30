<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Policies;

use App\Models\User;

class AbsencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'PROF', 'SECRETAIRE', 'PARENT', 'ELEVE']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'PROF', 'SECRETAIRE']);
    }
}
