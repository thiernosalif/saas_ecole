<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Services;

use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Notifications\IdentifiantsCompteStaff;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompteUtilisateurService
{
    public function creerCompteStaff(array $donnees, string $role): User
    {
        return DB::transaction(function () use ($donnees, $role) {
            $personne = Personne::create([
                'type' => $role === 'PROF' ? Personne::TYPE_PROF : Personne::TYPE_STAFF,
                'nom' => $donnees['nom'],
                'prenom' => $donnees['prenom'],
                'telephone' => $donnees['telephone'] ?? null,
                'email' => $donnees['email'],
            ]);

            $motDePasseTemporaire = Str::password(12);

            $user = User::create([
                'tenant_id' => app('currentTenantId'),
                'personne_id' => $personne->id,
                'name' => trim("{$donnees['prenom']} {$donnees['nom']}"),
                'email' => $donnees['email'],
                'password' => $motDePasseTemporaire,
            ]);

            $user->assignRole($role);

            $user->notify(new IdentifiantsCompteStaff(app('currentTenant'), $role, $motDePasseTemporaire));

            return $user->fresh('personne');
        });
    }

    public function lister(): Collection
    {
        return User::where('tenant_id', app('currentTenantId'))
            ->whereNotNull('personne_id')
            ->whereHas('personne', fn ($query) => $query->whereIn('type', [Personne::TYPE_PROF, Personne::TYPE_STAFF]))
            ->with('personne')
            ->get();
    }

    public function trouver(string $id): User
    {
        return User::where('tenant_id', app('currentTenantId'))
            ->whereNotNull('personne_id')
            ->with('personne')
            ->findOrFail($id);
    }

    public function desactiver(User $compte): void
    {
        DB::transaction(function () use ($compte) {
            $compte->personne->update(['actif' => false]);
        });
    }
}
