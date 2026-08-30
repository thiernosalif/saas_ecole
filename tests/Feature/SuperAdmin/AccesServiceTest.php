<?php

declare(strict_types=1);

use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Notifications\EtablissementSuspendu;
use App\Domain\SuperAdmin\Services\AccesService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function creerEcoleAvecDirecteur(string $slug): array
{
    $etablissement = Etablissement::create([
        'tenant_id' => $slug,
        'nom' => "École {$slug}",
        'sous_domaine' => $slug,
        'statut' => Etablissement::STATUT_ACTIF,
    ]);

    $directeur = User::factory()->create(['tenant_id' => $slug]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
    Role::create(['name' => 'ECOLE_ADMIN', 'guard_name' => 'web', 'team_id' => $etablissement->id]);
    $directeur->assignRole('ECOLE_ADMIN');

    return [$etablissement, $directeur];
}

it('suspend une école, révoque ses tokens actifs et notifie le directeur sans supprimer de données', function () {
    Notification::fake();
    [$etablissement, $directeur] = creerEcoleAvecDirecteur('ecole-suspendue');
    $directeur->createToken('mobile');

    $etablissement = app(AccesService::class)->suspendre($etablissement, 'Impayé depuis 30 jours');

    expect($etablissement->statut)->toBe(Etablissement::STATUT_SUSPENDU)
        ->and($etablissement->motif_suspension)->toBe('Impayé depuis 30 jours')
        ->and($etablissement->date_suspension)->not->toBeNull()
        ->and($directeur->fresh()->tokens()->count())->toBe(0);

    Notification::assertSentTo($directeur, EtablissementSuspendu::class);
});

it('bloque l’accès au sous-domaine d’une école suspendue via ResolveTenant', function () {
    [$etablissement, $directeur] = creerEcoleAvecDirecteur('ecole-bloquee');
    app(AccesService::class)->suspendre($etablissement, 'Test');

    $this->actingAs($directeur)
        ->get('http://ecole-bloquee.plateforme.sn.localhost/eleves')
        ->assertForbidden();
});

it('réactive une école suspendue et restaure immédiatement l’accès', function () {
    [$etablissement, $directeur] = creerEcoleAvecDirecteur('ecole-reactivee');
    $acces = app(AccesService::class);

    $acces->suspendre($etablissement, 'Test');
    $etablissement = $acces->reactiver($etablissement->fresh());

    expect($etablissement->statut)->toBe(Etablissement::STATUT_ACTIF)
        ->and($etablissement->motif_suspension)->toBeNull();

    $this->actingAs($directeur)
        ->get('http://ecole-reactivee.plateforme.sn.localhost/eleves')
        ->assertOk();
});

/**
 * Bug trouvé en Session 9 en câblant l'onglet "accès" du portail : archiver()
 * mettait bien statut = ARCHIVE mais ne révoquait pas les tokens Sanctum
 * actifs (contrairement à suspendre()), et ResolveTenant ne bloquait que
 * SUSPENDU — une école archivée gardait donc un accès web totalement
 * fonctionnel, alors que §15.4 exige un accès "définitivement coupé".
 */
it('archive une école, révoque ses tokens actifs et bloque l’accès au sous-domaine, comme une suspension', function () {
    Storage::fake('s3');
    [$etablissement, $directeur] = creerEcoleAvecDirecteur('ecole-archivee-acces');
    $directeur->createToken('mobile');

    $etablissement = app(AccesService::class)->archiver($etablissement);

    expect($etablissement->statut)->toBe(Etablissement::STATUT_ARCHIVE)
        ->and($etablissement->date_resiliation)->not->toBeNull()
        ->and($directeur->fresh()->tokens()->count())->toBe(0);

    $this->actingAs($directeur)
        ->get('http://ecole-archivee-acces.plateforme.sn.localhost/eleves')
        ->assertForbidden();
});
