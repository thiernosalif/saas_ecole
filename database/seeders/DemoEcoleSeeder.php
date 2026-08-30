<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Scolarite\Models\Niveau;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Jeu de données de démo pour vérifier manuellement le portail Livewire de la
 * Session 6 (§14) : une école, un utilisateur par rôle (ECOLE_ADMIN, SCOLARITE,
 * PROF), une classe et quelques élèves inscrits. Mot de passe identique pour les
 * trois comptes de démo : "password".
 */
class DemoEcoleSeeder extends Seeder
{
    public function run(): void
    {
        $etablissement = Etablissement::create([
            'tenant_id' => 'demo',
            'nom' => 'École Démo',
            'sous_domaine' => 'demo',
            'statut' => 'ACTIF',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);

        foreach (['ECOLE_ADMIN', 'SCOLARITE', 'PROF'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web', 'team_id' => $etablissement->id]);
        }

        $admin = User::factory()->create([
            'tenant_id' => 'demo',
            'name' => 'Admin Démo',
            'email' => 'admin@demo.test',
        ]);
        $admin->assignRole('ECOLE_ADMIN');

        $scolarite = User::factory()->create([
            'tenant_id' => 'demo',
            'name' => 'Scolarité Démo',
            'email' => 'scolarite@demo.test',
        ]);
        $scolarite->assignRole('SCOLARITE');

        $prof = User::factory()->create([
            'tenant_id' => 'demo',
            'name' => 'Prof Démo',
            'email' => 'prof@demo.test',
        ]);
        $prof->assignRole('PROF');

        $niveau = Niveau::create([
            'tenant_id' => 'demo',
            'libelle' => '6ème',
            'ordre' => 1,
            'cycle' => Niveau::CYCLE_SECONDAIRE,
        ]);

        $annee = AnneeScolaire::create([
            'tenant_id' => 'demo',
            'libelle' => '2025-2026',
            'date_debut' => '2025-10-01',
            'date_fin' => '2026-07-31',
            'active' => true,
        ]);

        $classe = Classe::create([
            'tenant_id' => 'demo',
            'niveau_id' => $niveau->id,
            'annee_id' => $annee->id,
            'libelle' => '6ème A',
            'capacite_max' => 40,
        ]);

        collect([
            ['nom' => 'Diop', 'prenom' => 'Awa', 'genre' => 'F'],
            ['nom' => 'Ba', 'prenom' => 'Moussa', 'genre' => 'M'],
            ['nom' => 'Fall', 'prenom' => 'Fatou', 'genre' => 'F'],
        ])->each(function (array $data) use ($classe, $annee): void {
            $eleve = Personne::create([
                'tenant_id' => 'demo',
                'type' => Personne::TYPE_ELEVE,
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'genre' => $data['genre'],
                'date_naissance' => '2013-01-01',
                'num_acte_naissance' => strtoupper($data['nom']).'-001',
            ]);

            Inscription::create([
                'tenant_id' => 'demo',
                'eleve_id' => $eleve->id,
                'classe_id' => $classe->id,
                'annee_id' => $annee->id,
                'statut' => Inscription::STATUT_ACTIVE,
                'date_inscription' => '2025-10-01',
            ]);
        });
    }
}
