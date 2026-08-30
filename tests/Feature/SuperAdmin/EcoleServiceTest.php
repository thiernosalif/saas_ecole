<?php

declare(strict_types=1);

use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Notifications\InvitationDirecteur;
use App\Domain\SuperAdmin\Services\EcoleService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

it('crée une école avec un sous-domaine unique, ses rôles par défaut et un directeur invité', function () {
    Notification::fake();

    $etablissement = app(EcoleService::class)->creerEcole([
        'nom' => 'Lycée Blaise Diagne',
        'email_directeur' => 'directeur@lycee-bd.test',
        'essai_gratuit' => true,
    ]);

    expect($etablissement->tenant_id)->toBe('lycee-blaise-diagne')
        ->and($etablissement->sous_domaine)->toBe('lycee-blaise-diagne')
        ->and($etablissement->statut)->toBe(Etablissement::STATUT_ACTIF)
        ->and($etablissement->date_fin_essai)->not->toBeNull();

    $directeur = User::where('email', 'directeur@lycee-bd.test')->firstOrFail();
    expect($directeur->tenant_id)->toBe('lycee-blaise-diagne');

    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
    expect($directeur->hasRole('ECOLE_ADMIN'))->toBeTrue();

    Notification::assertSentTo($directeur, InvitationDirecteur::class);
});

it('désambiguïse le tenant_id quand deux écoles ont un nom proche', function () {
    Notification::fake();
    $service = app(EcoleService::class);

    $ecole1 = $service->creerEcole(['nom' => 'École Sénégal', 'email_directeur' => 'a@test.test']);
    $ecole2 = $service->creerEcole(['nom' => 'École Sénégal', 'email_directeur' => 'b@test.test']);

    expect($ecole1->tenant_id)->toBe('ecole-senegal')
        ->and($ecole2->tenant_id)->toBe('ecole-senegal-1');
});
