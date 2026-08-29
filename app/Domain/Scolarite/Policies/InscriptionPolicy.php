<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Policies;

use App\Domain\Scolarite\Models\Inscription;
use App\Models\User;

class InscriptionPolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE']);
    }

    public function update(User $user, ?Inscription $inscription = null): bool
    {
        return $user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE']);
    }
}
