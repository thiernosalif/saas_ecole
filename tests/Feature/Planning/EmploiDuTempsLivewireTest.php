<?php

declare(strict_types=1);

use App\Domain\Planning\Livewire\EmploiDuTemps;
use App\Domain\Planning\Models\Salle;
use App\Domain\Planning\Models\Seance;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Niveau;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * Test obligatoire (cf. règle de session) pour toute nouvelle surface exposant des
 * données scopées par tenant : la grille /emploi-du-temps ne doit jamais laisser
 * fuiter les séances d'une autre école, même si la lecture passe par le portail web
 * Livewire plutôt que par SeanceService/l'API mobile (déjà couverts en Session 10).
 */
function creerEcoleAvecClasseEtSeance(string $slug, string $libelleMatiere): array
{
    $etablissement = Etablissement::create([
        'tenant_id' => $slug,
        'nom' => "École {$slug}",
        'sous_domaine' => $slug,
        'statut' => 'ACTIF',
    ]);

    $user = User::factory()->create(['tenant_id' => $slug]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
    Role::create(['name' => 'ECOLE_ADMIN', 'guard_name' => 'web', 'team_id' => $etablissement->id]);
    $user->assignRole('ECOLE_ADMIN');

    $niveau = Niveau::withoutTenant()->create(['tenant_id' => $slug, 'libelle' => 'Niveau', 'ordre' => 1]);
    $classe = Classe::withoutTenant()->create(['tenant_id' => $slug, 'niveau_id' => $niveau->id, 'libelle' => "Classe {$slug}"]);
    $matiere = \App\Domain\Notes\Models\Matiere::withoutTenant()->create([
        'tenant_id' => $slug, 'libelle' => $libelleMatiere, 'niveau_id' => $niveau->id, 'coefficient' => 1,
    ]);
    $salle = Salle::withoutTenant()->create(['tenant_id' => $slug, 'nom' => "Salle {$slug}"]);

    Seance::withoutTenant()->create([
        'tenant_id' => $slug,
        'classe_id' => $classe->id,
        'matiere_id' => $matiere->id,
        'salle_id' => $salle->id,
        'jour_semaine' => 0,
        'heure_debut' => '08:00',
        'heure_fin' => '09:00',
    ]);

    return [$etablissement, $user, $classe];
}

it('n’affiche que les séances de la classe et du tenant résolus sur la page Livewire', function () {
    [$ecoleA, $adminA, $classeA] = creerEcoleAvecClasseEtSeance('ecole-a', 'Mathematiques');
    creerEcoleAvecClasseEtSeance('ecole-b', 'HistoireGeographie');

    Livewire::actingAs($adminA);
    app()->instance('currentTenantId', $ecoleA->tenant_id);
    app()->instance('currentTenant', $ecoleA);
    app(PermissionRegistrar::class)->setPermissionsTeamId($ecoleA->id);

    Livewire::test(EmploiDuTemps::class)
        ->set('classe_id', $classeA->id)
        ->assertSee('Mathematiques')
        ->assertDontSee('HistoireGeographie');
});

it('redirige un visiteur non authentifié vers la connexion', function () {
    Etablissement::create([
        'tenant_id' => 'ecole-a',
        'nom' => 'École ecole-a',
        'sous_domaine' => 'ecole-a',
        'statut' => 'ACTIF',
    ]);

    $this->get('http://ecole-a.plateforme.sn.localhost/emploi-du-temps')
        ->assertRedirect(route('login'));
});

it('refuse l’accès à un rôle non autorisé avec un 403', function () {
    $etablissement = Etablissement::create([
        'tenant_id' => 'ecole-a',
        'nom' => 'École ecole-a',
        'sous_domaine' => 'ecole-a',
        'statut' => 'ACTIF',
    ]);

    $user = User::factory()->create(['tenant_id' => 'ecole-a']);
    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
    Role::create(['name' => 'PARENT', 'guard_name' => 'web', 'team_id' => $etablissement->id]);
    $user->assignRole('PARENT');

    $this->actingAs($user)
        ->get('http://ecole-a.plateforme.sn.localhost/emploi-du-temps')
        ->assertForbidden();
});

it('cache le bouton d’ajout et bloque la création pour un PROF', function () {
    [$ecole, , $classe] = creerEcoleAvecClasseEtSeance('ecole-a', 'Mathematiques');

    $prof = User::factory()->create(['tenant_id' => 'ecole-a']);
    app(PermissionRegistrar::class)->setPermissionsTeamId($ecole->id);
    Role::create(['name' => 'PROF', 'guard_name' => 'web', 'team_id' => $ecole->id]);
    $prof->assignRole('PROF');

    // On vérifie l'attribut wire:click plutôt que le libellé du bouton : le titre de
    // la modale ("Ajouter une séance") est toujours présent dans le HTML servi (elle
    // est juste masquée côté client par Alpine via x-show), donc assertDontSee sur ce
    // libellé donnerait un faux échec même quand le bouton lui-même est bien absent.
    $this->actingAs($prof)
        ->get('http://ecole-a.plateforme.sn.localhost/emploi-du-temps')
        ->assertOk()
        ->assertDontSee('wire:click="openCreate"', false);

    Livewire::actingAs($prof);
    app()->instance('currentTenantId', $ecole->tenant_id);
    app()->instance('currentTenant', $ecole);
    app(PermissionRegistrar::class)->setPermissionsTeamId($ecole->id);

    Livewire::test(EmploiDuTemps::class)
        ->set('classe_id', $classe->id)
        ->call('openCreate')
        ->assertForbidden();
});
