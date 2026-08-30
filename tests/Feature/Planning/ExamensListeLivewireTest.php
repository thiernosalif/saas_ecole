<?php

declare(strict_types=1);

use App\Domain\Notes\Models\Matiere;
use App\Domain\Planning\Livewire\ExamensListe;
use App\Domain\Planning\Models\Examen;
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
 * données scopées par tenant : la liste /examens ne doit jamais laisser fuiter les
 * examens d'une autre école.
 */
function creerEcoleAvecClasseEtExamen(string $slug, string $libelle): array
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
    $matiere = Matiere::withoutTenant()->create(['tenant_id' => $slug, 'libelle' => 'Matiere', 'niveau_id' => $niveau->id, 'coefficient' => 1]);

    Examen::withoutTenant()->create([
        'tenant_id' => $slug,
        'classe_id' => $classe->id,
        'matiere_id' => $matiere->id,
        'date_examen' => now()->addWeek()->format('Y-m-d'),
        'libelle' => $libelle,
    ]);

    return [$etablissement, $user, $classe];
}

it('n’affiche que les examens du tenant résolu sur la page Livewire', function () {
    [$ecoleA, $adminA] = creerEcoleAvecClasseEtExamen('ecole-a', 'Composition de Mathematiques');
    creerEcoleAvecClasseEtExamen('ecole-b', 'Composition de Sciences Naturelles');

    Livewire::actingAs($adminA);
    app()->instance('currentTenantId', $ecoleA->tenant_id);
    app()->instance('currentTenant', $ecoleA);
    app(PermissionRegistrar::class)->setPermissionsTeamId($ecoleA->id);

    Livewire::test(ExamensListe::class)
        ->assertSee('Composition de Mathematiques')
        ->assertDontSee('Composition de Sciences Naturelles');
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
        ->get('http://ecole-a.plateforme.sn.localhost/examens')
        ->assertForbidden();
});

it('cache le bouton d’ajout et bloque la création pour un PROF', function () {
    [$ecole] = creerEcoleAvecClasseEtExamen('ecole-a', 'Composition de Mathematiques');

    $prof = User::factory()->create(['tenant_id' => 'ecole-a']);
    app(PermissionRegistrar::class)->setPermissionsTeamId($ecole->id);
    Role::create(['name' => 'PROF', 'guard_name' => 'web', 'team_id' => $ecole->id]);
    $prof->assignRole('PROF');

    $this->actingAs($prof)
        ->get('http://ecole-a.plateforme.sn.localhost/examens')
        ->assertOk()
        ->assertDontSee('wire:click="openCreate"', false);

    Livewire::actingAs($prof);
    app()->instance('currentTenantId', $ecole->tenant_id);
    app()->instance('currentTenant', $ecole);
    app(PermissionRegistrar::class)->setPermissionsTeamId($ecole->id);

    Livewire::test(ExamensListe::class)
        ->call('openCreate')
        ->assertForbidden();
});
