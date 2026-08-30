<?php

declare(strict_types=1);

use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Notifications\IdentifiantsCompteStaff;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function creerEcoleAvecStaffRoles(string $slug): Etablissement
{
    $etablissement = Etablissement::create([
        'tenant_id' => $slug,
        'nom' => "École {$slug}",
        'sous_domaine' => $slug,
        'statut' => 'ACTIF',
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);

    foreach (['ECOLE_ADMIN', 'SCOLARITE', 'PROF', 'COMPTABLE', 'SECRETAIRE'] as $role) {
        Role::create(['name' => $role, 'guard_name' => 'web', 'team_id' => $etablissement->id]);
    }

    return $etablissement;
}

it('permet à un ECOLE_ADMIN de créer un compte PROF avec Personne + User + notification', function () {
    Notification::fake();

    $ecole = creerEcoleAvecStaffRoles('ecole-a');
    $admin = User::factory()->create(['tenant_id' => $ecole->tenant_id]);
    $admin->assignRole('ECOLE_ADMIN');

    Sanctum::actingAs($admin, ['*']);

    $response = $this->postJson('http://ecole-a.plateforme.sn.localhost/api/v1/comptes', [
        'nom' => 'Diop',
        'prenom' => 'Awa',
        'email' => 'awa.diop@ecole-a.test',
        'telephone' => '771234567',
        'role' => 'PROF',
    ])->assertCreated();

    $userId = $response->json('data.id');
    $user = User::find($userId);

    expect($user->tenant_id)->toBe('ecole-a')
        ->and($user->personne_id)->not->toBeNull()
        ->and($user->personne->type)->toBe(Personne::TYPE_PROF)
        ->and($user->hasRole('PROF'))->toBeTrue();

    Notification::assertSentTo($user, IdentifiantsCompteStaff::class);
});

it('permet à un ECOLE_ADMIN de créer un compte COMPTABLE ou SECRETAIRE (Personne type=STAFF)', function (string $role) {
    Notification::fake();

    $ecole = creerEcoleAvecStaffRoles('ecole-a');
    $admin = User::factory()->create(['tenant_id' => $ecole->tenant_id]);
    $admin->assignRole('ECOLE_ADMIN');

    Sanctum::actingAs($admin, ['*']);

    $response = $this->postJson('http://ecole-a.plateforme.sn.localhost/api/v1/comptes', [
        'nom' => 'Sow',
        'prenom' => 'Ibrahima',
        'email' => strtolower($role).'@ecole-a.test',
        'role' => $role,
    ])->assertCreated();

    $user = User::find($response->json('data.id'));

    expect($user->personne->type)->toBe(Personne::TYPE_STAFF)
        ->and($user->hasRole($role))->toBeTrue();

    Notification::assertSentTo($user, IdentifiantsCompteStaff::class);
})->with(['COMPTABLE', 'SECRETAIRE']);

it('ne liste que les comptes staff du tenant courant', function () {
    $ecole = creerEcoleAvecStaffRoles('ecole-a');
    $admin = User::factory()->create(['tenant_id' => $ecole->tenant_id]);
    $admin->assignRole('ECOLE_ADMIN');

    Sanctum::actingAs($admin, ['*']);

    $this->postJson('http://ecole-a.plateforme.sn.localhost/api/v1/comptes', [
        'nom' => 'Ba',
        'prenom' => 'Moussa',
        'email' => 'moussa.ba@ecole-a.test',
        'role' => 'SCOLARITE',
    ])->assertCreated();

    $response = $this->getJson('http://ecole-a.plateforme.sn.localhost/api/v1/comptes')->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.email'))->toBe('moussa.ba@ecole-a.test');
});

it('refuse la création d’un compte staff par un SCOLARITE (réservé à ECOLE_ADMIN)', function () {
    $ecole = creerEcoleAvecStaffRoles('ecole-a');
    $scolarite = User::factory()->create(['tenant_id' => $ecole->tenant_id]);
    $scolarite->assignRole('SCOLARITE');

    Sanctum::actingAs($scolarite, ['*']);

    $this->postJson('http://ecole-a.plateforme.sn.localhost/api/v1/comptes', [
        'nom' => 'Fall',
        'prenom' => 'Fatou',
        'email' => 'fatou.fall@ecole-a.test',
        'role' => 'PROF',
    ])->assertForbidden();
});
