<?php

declare(strict_types=1);

use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * Test obligatoire pour ce module (cf. règle de la tenant-isolation étendue
 * aux modules non tenant-scopés) : le risque n°1 du portail Super Admin n'est
 * pas une fuite inter-tenant (Etablissement est global par design) mais une
 * élévation de privilège — un compte école qui atteindrait admin.plateforme.sn.
 */
function creerSuperAdmin(): User
{
    app(PermissionRegistrar::class)->setPermissionsTeamId(Etablissement::PLATFORM_TEAM_ID);
    Role::firstOrCreate(['name' => 'SUPER_ADMIN', 'guard_name' => 'web', 'team_id' => Etablissement::PLATFORM_TEAM_ID]);

    $user = User::factory()->create(['tenant_id' => null]);
    $user->assignRole('SUPER_ADMIN');

    return $user;
}

function creerEcoleAdmin(): array
{
    $etablissement = Etablissement::create([
        'tenant_id' => 'ecole-x',
        'nom' => 'École X',
        'sous_domaine' => 'ecole-x',
        'statut' => 'ACTIF',
    ]);

    $admin = User::factory()->create(['tenant_id' => 'ecole-x']);

    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
    Role::create(['name' => 'ECOLE_ADMIN', 'guard_name' => 'web', 'team_id' => $etablissement->id]);
    $admin->assignRole('ECOLE_ADMIN');

    return [$etablissement, $admin];
}

it('refuse l’accès au portail admin à un visiteur non authentifié', function () {
    $this->get('http://admin.plateforme.sn.localhost/admin/analytics')
        ->assertRedirect();
});

it('refuse l’accès au portail admin à un ECOLE_ADMIN d’une école, même authentifié', function () {
    [, $ecoleAdmin] = creerEcoleAdmin();

    $this->actingAs($ecoleAdmin)
        ->get('http://admin.plateforme.sn.localhost/admin/analytics')
        ->assertForbidden();
});

it('autorise un SUPER_ADMIN à consulter les analytics de la plateforme', function () {
    $superAdmin = creerSuperAdmin();

    $this->actingAs($superAdmin)
        ->getJson('http://admin.plateforme.sn.localhost/admin/analytics')
        ->assertOk()
        ->assertJsonStructure(['mrr', 'arr', 'arpu', 'churn', 'ltv', 'nb_ecoles_actives', 'nb_ecoles_suspendues', 'croissance']);
});

it('autorise un SUPER_ADMIN à lister toutes les écoles, tous tenants confondus', function () {
    creerEcoleAdmin();
    $superAdmin = creerSuperAdmin();

    $this->actingAs($superAdmin)
        ->getJson('http://admin.plateforme.sn.localhost/admin/ecoles')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
