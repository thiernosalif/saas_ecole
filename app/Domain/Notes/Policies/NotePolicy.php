<?php

declare(strict_types=1);

namespace App\Domain\Notes\Policies;

use App\Domain\Notes\Models\Note;
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

    public function update(User $user, Note $note): bool
    {
        if ($user->hasAnyRole(['ECOLE_ADMIN', 'SCOLARITE'])) {
            return true;
        }

        return $user->hasRole('PROF') && $note->saisie_par === $user->personne_id;
    }

    public function delete(User $user, Note $note): bool
    {
        return $this->update($user, $note);
    }
}
