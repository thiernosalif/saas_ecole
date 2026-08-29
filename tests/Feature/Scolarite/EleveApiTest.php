<?php

declare(strict_types=1);

use App\Domain\Scolarite\Models\Personne;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Etablissement::create([
        'tenant_id' => 'ecole-test',
        'nom' => 'École Test',
        'sous_domaine' => 'ecole-test',
        'statut' => 'ACTIF',
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->ecole->id);
});

function utilisateurAvecRole(Etablissement $ecole, string $role): User
{
    $user = User::factory()->create(['tenant_id' => $ecole->tenant_id]);
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web', 'team_id' => $ecole->id]);
    $user->assignRole($role);

    return $user;
}

it('permet à un ECOLE_ADMIN de créer, lire, modifier et supprimer un élève', function () {
    $admin = utilisateurAvecRole($this->ecole, 'ECOLE_ADMIN');
    Sanctum::actingAs($admin, ['*']);

    $store = $this->postJson('http://ecole-test.plateforme.sn.localhost/api/v1/eleves', [
        'nom' => 'Fall',
        'prenom' => 'Khady',
        'date_naissance' => '2012-05-10',
        'num_acte_naissance' => 'X-042',
    ])->assertCreated();

    $eleveId = $store->json('data.id');

    expect(Personne::withoutTenant()->find($eleveId)->type)->toBe(Personne::TYPE_ELEVE);

    $this->getJson("http://ecole-test.plateforme.sn.localhost/api/v1/eleves/{$eleveId}")
        ->assertOk()
        ->assertJsonPath('data.nom', 'Fall');

    $this->putJson("http://ecole-test.plateforme.sn.localhost/api/v1/eleves/{$eleveId}", [
        'nom' => 'Fall-Diagne',
    ])->assertOk()->assertJsonPath('data.nom', 'Fall-Diagne');

    $this->deleteJson("http://ecole-test.plateforme.sn.localhost/api/v1/eleves/{$eleveId}")
        ->assertNoContent();

    expect(Personne::withoutTenant()->find($eleveId))->toBeNull();
});

it('refuse à un PROF la création d’un élève', function () {
    $prof = utilisateurAvecRole($this->ecole, 'PROF');
    Sanctum::actingAs($prof, ['*']);

    $this->postJson('http://ecole-test.plateforme.sn.localhost/api/v1/eleves', [
        'nom' => 'Fall',
        'prenom' => 'Khady',
        'date_naissance' => '2012-05-10',
        'num_acte_naissance' => 'X-042',
    ])->assertForbidden();
});
