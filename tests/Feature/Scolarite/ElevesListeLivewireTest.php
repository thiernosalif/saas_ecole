<?php

declare(strict_types=1);

use App\Domain\Scolarite\Livewire\EleveForm;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * Test obligatoire (cf. règle de session) pour toute nouvelle surface exposant des
 * données scopées par tenant : la page Livewire /eleves ne doit jamais laisser
 * fuiter les élèves d'une autre école, même si le rendu passe par le portail web
 * plutôt que par l'API.
 */
function creerEcoleAvecUtilisateur(string $slug, string $role): array
{
    $etablissement = Etablissement::create([
        'tenant_id' => $slug,
        'nom' => "École {$slug}",
        'sous_domaine' => $slug,
        'statut' => 'ACTIF',
    ]);

    $user = User::factory()->create(['tenant_id' => $slug]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
    Role::create(['name' => $role, 'guard_name' => 'web', 'team_id' => $etablissement->id]);
    $user->assignRole($role);

    return [$etablissement, $user];
}

it('n’affiche que les élèves du tenant résolu depuis le sous-domaine sur la page Livewire', function () {
    [$ecoleA, $adminA] = creerEcoleAvecUtilisateur('ecole-a', 'ECOLE_ADMIN');
    [$ecoleB] = creerEcoleAvecUtilisateur('ecole-b', 'ECOLE_ADMIN');

    Personne::withoutTenant()->create([
        'tenant_id' => $ecoleA->tenant_id,
        'type' => Personne::TYPE_ELEVE,
        'nom' => 'Diop',
        'prenom' => 'Awa',
        'num_acte_naissance' => 'A-001',
    ]);

    Personne::withoutTenant()->create([
        'tenant_id' => $ecoleB->tenant_id,
        'type' => Personne::TYPE_ELEVE,
        'nom' => 'Ba',
        'prenom' => 'Moussa',
        'num_acte_naissance' => 'B-001',
    ]);

    $this->actingAs($adminA)
        ->get('http://ecole-a.plateforme.sn.localhost/eleves')
        ->assertOk()
        ->assertSee('Diop')
        ->assertDontSee('Ba');
});

it('redirige un visiteur non authentifié vers la connexion', function () {
    Etablissement::create([
        'tenant_id' => 'ecole-a',
        'nom' => 'École ecole-a',
        'sous_domaine' => 'ecole-a',
        'statut' => 'ACTIF',
    ]);

    $this->get('http://ecole-a.plateforme.sn.localhost/eleves')
        ->assertRedirect(route('login'));
});

it('refuse l’accès à un rôle non autorisé avec un 403', function () {
    [, $parent] = creerEcoleAvecUtilisateur('ecole-a', 'PARENT');

    $this->actingAs($parent)
        ->get('http://ecole-a.plateforme.sn.localhost/eleves')
        ->assertForbidden();
});

it('cache le bouton d’ajout et bloque la création pour un PROF', function () {
    [$ecole, $prof] = creerEcoleAvecUtilisateur('ecole-a', 'PROF');

    $this->actingAs($prof)
        ->get('http://ecole-a.plateforme.sn.localhost/eleves')
        ->assertOk()
        ->assertDontSee('Ajouter un élève');

    Livewire::actingAs($prof);
    app()->instance('currentTenantId', $ecole->tenant_id);
    app()->instance('currentTenant', $ecole);
    app(PermissionRegistrar::class)->setPermissionsTeamId($ecole->id);

    Livewire::test(EleveForm::class)
        ->assertForbidden();
});
