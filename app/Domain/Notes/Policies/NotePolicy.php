<?php

declare(strict_types=1);

namespace App\Domain\Notes\Policies;

use App\Models\User;

class NotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'PROF', 'PARENT', 'ELEVE']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE', 'PROF']);
    }
}
