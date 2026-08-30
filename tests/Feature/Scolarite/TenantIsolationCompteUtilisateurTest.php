<?php

declare(strict_types=1);

use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * Test obligatoire depuis la Session 4 (cf. TenantIsolationEleveTest) : un
 * ECOLE_ADMIN de l'école A ne doit ni voir ni pouvoir agir sur un compte de
 * l'école B. `User` n'a pas de TenantScope (contrairement à `Personne`) donc
 * ce test vérifie que le filtrage manuel par tenant_id dans
 * CompteUtilisateurService protège bien contre l'IDOR.
 */
function creerEcoleAvecAdminEtStaffRoles(string $slug): array
{
    $etablissement = Etablissement::create([
        'tenant_id' => $slug,
        'nom' => "École {$slug}",
        'sous_domaine' => $slug,
        'statut' => 'ACTIF',
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);

    foreach (['ECOLE_ADMIN', 'SCOLARITE', 'PROF'] as $role) {
        Role::create(['name' => $role, 'guard_name' => 'web', 'team_id' => $etablissement->id]);
    }

    $admin = User::factory()->create(['tenant_id' => $slug]);
    $admin->assignRole('ECOLE_ADMIN');

    return [$etablissement, $admin];
}

it('ne fait pas apparaître les comptes staff d’un autre tenant dans la liste', function () {
    [$ecoleA, $adminA] = creerEcoleAvecAdminEtStaffRoles('ecole-a');
    [$ecoleB, $adminB] = creerEcoleAvecAdminEtStaffRoles('ecole-b');

    Sanctum::actingAs($adminB, ['*']);
    $this->postJson('http://ecole-b.plateforme.sn.localhost/api/v1/comptes', [
        'nom' => 'Ba', 'prenom' => 'Moussa', 'email' => 'moussa.ba@ecole-b.test', 'role' => 'PROF',
    ])->assertCreated();

    Sanctum::actingAs($adminA, ['*']);
    $response = $this->getJson('http://ecole-a.plateforme.sn.localhost/api/v1/comptes')->assertOk();

    expect($response->json('data'))->toBeEmpty();
});

it('bloque la désactivation d’un compte d’un autre tenant avec un 404, pas une fuite de données', function () {
    [$ecoleA, $adminA] = creerEcoleAvecAdminEtStaffRoles('ecole-a');
    [$ecoleB, $adminB] = creerEcoleAvecAdminEtStaffRoles('ecole-b');

    Sanctum::actingAs($adminB, ['*']);
    $response = $this->postJson('http://ecole-b.plateforme.sn.localhost/api/v1/comptes', [
        'nom' => 'Ba', 'prenom' => 'Moussa', 'email' => 'moussa.ba@ecole-b.test', 'role' => 'PROF',
    ])->assertCreated();

    $compteBId = $response->json('data.id');

    Sanctum::actingAs($adminA, ['*']);
    $this->patchJson("http://ecole-a.plateforme.sn.localhost/api/v1/comptes/{$compteBId}/desactiver")
        ->assertNotFound();

    app()->instance('currentTenantId', $ecoleB->tenant_id);
    $personneB = User::find($compteBId)->personne;
    expect($personneB->fresh()->actif)->toBeTrue();
});
