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

/**
 * Test obligatoire à partir de la Session 4 (premier module métier réel au-dessus
 * du socle tenant/auth) : vérifie que le TenantScope + ResolveTenant isolent
 * correctement les données d'un module métier de bout en bout via l'API, pas
 * seulement sur le modèle de test générique de la Session 2/3.
 */
function creerEcoleAvecAdmin(string $slug): array
{
    $etablissement = Etablissement::create([
        'tenant_id' => $slug,
        'nom' => "École {$slug}",
        'sous_domaine' => $slug,
        'statut' => 'ACTIF',
    ]);

    $admin = User::factory()->create(['tenant_id' => $slug]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
    Role::create(['name' => 'ECOLE_ADMIN', 'guard_name' => 'web', 'team_id' => $etablissement->id]);
    $admin->assignRole('ECOLE_ADMIN');

    return [$etablissement, $admin];
}

it('ne retourne que les élèves du tenant résolu depuis le sous-domaine', function () {
    [$ecoleA, $adminA] = creerEcoleAvecAdmin('ecole-a');
    [$ecoleB] = creerEcoleAvecAdmin('ecole-b');

    $eleveA = Personne::withoutTenant()->create([
        'tenant_id' => $ecoleA->tenant_id,
        'type' => Personne::TYPE_ELEVE,
        'nom' => 'Diop',
        'prenom' => 'Awa',
        'num_acte_naissance' => 'A-001',
    ]);

    $eleveB = Personne::withoutTenant()->create([
        'tenant_id' => $ecoleB->tenant_id,
        'type' => Personne::TYPE_ELEVE,
        'nom' => 'Ba',
        'prenom' => 'Moussa',
        'num_acte_naissance' => 'B-001',
    ]);

    Sanctum::actingAs($adminA, ['*']);

    $response = $this->getJson('http://ecole-a.plateforme.sn.localhost/api/v1/eleves')
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toHaveCount(1)
        ->and($ids->first())->toBe($eleveA->id)
        ->and($ids)->not->toContain($eleveB->id);
});

it('bloque l’accès à un élève d’un autre tenant avec un 404, pas une fuite de données', function () {
    [$ecoleA, $adminA] = creerEcoleAvecAdmin('ecole-a');
    [$ecoleB] = creerEcoleAvecAdmin('ecole-b');

    $eleveB = Personne::withoutTenant()->create([
        'tenant_id' => $ecoleB->tenant_id,
        'type' => Personne::TYPE_ELEVE,
        'nom' => 'Ba',
        'prenom' => 'Moussa',
        'num_acte_naissance' => 'B-001',
    ]);

    Sanctum::actingAs($adminA, ['*']);

    $this->getJson("http://ecole-a.plateforme.sn.localhost/api/v1/eleves/{$eleveB->id}")
        ->assertNotFound();
});
