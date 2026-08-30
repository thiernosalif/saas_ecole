<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Services;

use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Notifications\InvitationDirecteur;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Étapes 3-4 de l'onboarding (§15.7) : création de l'établissement, du
 * sous-domaine, des rôles spatie par défaut et du compte directeur initial.
 * L'orchestration des 5 étapes (personnalisation incluse) vit dans
 * OnboardingService.
 */
class EcoleService
{
    private const ROLES_PAR_DEFAUT = ['ECOLE_ADMIN', 'SCOLARITE', 'PROF'];

    public function creerEcole(array $data): Etablissement
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $this->genererTenantIdUnique($data['nom']);

            $essaiGratuit = (bool) ($data['essai_gratuit'] ?? false);

            $etablissement = Etablissement::create([
                'tenant_id' => $tenantId,
                'nom' => $data['nom'],
                'sous_domaine' => $tenantId,
                'adresse' => $data['adresse'] ?? null,
                'ville' => $data['ville'] ?? null,
                'telephone_ecole' => $data['telephone_ecole'] ?? null,
                'contact_directeur' => $data['contact_directeur'] ?? null,
                'plan_id' => $data['plan_id'] ?? null,
                'date_debut_essai' => $essaiGratuit ? now()->toDateString() : null,
                'date_fin_essai' => $essaiGratuit ? now()->addDays(30)->toDateString() : null,
                'statut' => Etablissement::STATUT_ACTIF,
            ]);

            $this->seedRolesParDefaut($etablissement);
            $this->creerCompteDirecteur($etablissement, $data['email_directeur']);

            return $etablissement->fresh();
        });
    }

    public function seedRolesParDefaut(Etablissement $etablissement): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);

        foreach (self::ROLES_PAR_DEFAUT as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web', 'team_id' => $etablissement->id]);
        }
    }

    public function creerCompteDirecteur(Etablissement $etablissement, string $email): User
    {
        $motDePasseTemporaire = Str::password(12);

        $directeur = User::create([
            'tenant_id' => $etablissement->tenant_id,
            'name' => $etablissement->contact_directeur ?? "Directeur {$etablissement->nom}",
            'email' => $email,
            'password' => $motDePasseTemporaire,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
        $directeur->assignRole('ECOLE_ADMIN');

        $directeur->notify(new InvitationDirecteur($etablissement, $motDePasseTemporaire));

        return $directeur;
    }

    public function mettreAJour(Etablissement $etablissement, array $data): Etablissement
    {
        $etablissement->update(Arr::only($data, [
            'nom', 'adresse', 'telephone', 'telephone_ecole', 'email',
            'contact_directeur', 'ville', 'pays', 'plan_id',
            'nb_eleves_max', 'stockage_max_go',
        ]));

        return $etablissement->fresh();
    }

    public function supprimer(Etablissement $etablissement): void
    {
        $etablissement->delete();
    }

    private function genererTenantIdUnique(string $nom): string
    {
        $base = Str::slug($nom);
        $slug = $base;
        $suffixe = 1;

        while (Etablissement::where('tenant_id', $slug)->exists()) {
            $slug = "{$base}-{$suffixe}";
            $suffixe++;
        }

        return $slug;
    }
}
