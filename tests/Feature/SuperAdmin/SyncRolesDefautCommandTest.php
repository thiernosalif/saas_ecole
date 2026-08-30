<?php

declare(strict_types=1);

use App\Domain\SuperAdmin\Models\Etablissement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * Simule une école créée avant l'ajout de COMPTABLE/SECRETAIRE à
 * EcoleService::ROLES_PAR_DEFAUT (Session 14, extension) : seuls les 3
 * anciens rôles existent pour son team_id.
 */
it('crée les rôles manquants sur une école existante sans toucher aux rôles déjà là', function () {
    $etablissement = Etablissement::create([
        'tenant_id' => 'ecole-ancienne', 'nom' => 'École Ancienne', 'sous_domaine' => 'ecole-ancienne', 'statut' => 'ACTIF',
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
    foreach (['ECOLE_ADMIN', 'SCOLARITE', 'PROF'] as $role) {
        Role::create(['name' => $role, 'guard_name' => 'web', 'team_id' => $etablissement->id]);
    }

    Artisan::call('app:sync-roles-defaut');

    $noms = Role::where('team_id', $etablissement->id)->pluck('name');

    expect($noms)->toContain('ECOLE_ADMIN', 'SCOLARITE', 'PROF', 'COMPTABLE', 'SECRETAIRE')
        ->and($noms)->toHaveCount(5);
});

it('est idempotente : une seconde exécution ne crée aucun doublon', function () {
    $etablissement = Etablissement::create([
        'tenant_id' => 'ecole-ancienne', 'nom' => 'École Ancienne', 'sous_domaine' => 'ecole-ancienne', 'statut' => 'ACTIF',
    ]);

    Artisan::call('app:sync-roles-defaut');
    Artisan::call('app:sync-roles-defaut');

    expect(Role::where('team_id', $etablissement->id)->count())->toBe(5);
});
