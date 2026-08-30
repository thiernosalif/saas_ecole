<?php

declare(strict_types=1);

use App\Domain\SuperAdmin\Livewire\AnalyticsDashboard;
use App\Domain\SuperAdmin\Livewire\Communication;
use App\Domain\SuperAdmin\Livewire\EcoleDetail;
use App\Domain\SuperAdmin\Livewire\EcolesListe;
use App\Domain\SuperAdmin\Livewire\Onboarding;
use App\Domain\SuperAdmin\Models\CommunicationLog;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Models\PlanTarifaire;
use App\Domain\SuperAdmin\Notifications\AnnonceGlobale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * Le host de dev local (PHP 8.5 sans phpredis, cf. mémoire infra) corrompt le JS
 * Livewire/Alpine servi par `php artisan serve`, rendant impossible toute
 * vérification manuelle des interactions AJAX (recherche, wire:click, wire:submit)
 * dans un vrai navigateur pour cette session. Ces tests Livewire::test() exercent
 * donc directement le PHP des composants — c'est la vérification qui fait foi ici,
 * pas juste le rendu initial d'une page.
 */
function creerSuperAdminPourPortail(): User
{
    app(PermissionRegistrar::class)->setPermissionsTeamId(Etablissement::PLATFORM_TEAM_ID);
    Role::firstOrCreate(['name' => 'SUPER_ADMIN', 'guard_name' => 'web', 'team_id' => Etablissement::PLATFORM_TEAM_ID]);

    $user = User::factory()->create(['tenant_id' => null]);
    $user->assignRole('SUPER_ADMIN');

    return $user;
}

function creerEtablissementPourPortail(string $slug, string $statut = Etablissement::STATUT_ACTIF): Etablissement
{
    return Etablissement::create([
        'tenant_id' => $slug,
        'nom' => "École {$slug}",
        'sous_domaine' => $slug,
        'ville' => 'Dakar',
        'pays' => 'Sénégal',
        'statut' => $statut,
    ]);
}

// --- EcolesListe -------------------------------------------------------

it('filtre les écoles par recherche et par statut', function () {
    $superAdmin = creerSuperAdminPourPortail();
    creerEtablissementPourPortail('lycee-nord');
    creerEtablissementPourPortail('college-sud', Etablissement::STATUT_SUSPENDU);

    Livewire::actingAs($superAdmin)
        ->test(EcolesListe::class)
        ->assertSee('lycee-nord')
        ->assertSee('college-sud')
        ->set('search', 'nord')
        ->assertSee('lycee-nord')
        ->assertDontSee('college-sud')
        ->set('search', '')
        ->set('statutFiltre', 'SUSPENDU')
        ->assertDontSee('lycee-nord')
        ->assertSee('college-sud');
});

// --- EcoleDetail ---------------------------------------------------------

it('change d’onglet et met à jour les informations d’une école', function () {
    $superAdmin = creerSuperAdminPourPortail();
    $etablissement = creerEtablissementPourPortail('ecole-a-modifier');

    Livewire::actingAs($superAdmin)
        ->test(EcoleDetail::class, ['etablissement' => $etablissement])
        ->assertSet('activeTab', 'infos')
        ->call('setActiveTab', 'abonnement')
        ->assertSet('activeTab', 'abonnement')
        ->call('setActiveTab', 'infos')
        ->set('nom', 'Nouveau nom')
        ->set('ville', 'Thiès')
        ->call('updateInfos')
        ->assertHasNoErrors();

    expect($etablissement->fresh())
        ->nom->toBe('Nouveau nom')
        ->ville->toBe('Thiès');
});

it('suspend puis réactive une école depuis l’onglet accès', function () {
    Notification::fake();
    $superAdmin = creerSuperAdminPourPortail();
    $etablissement = creerEtablissementPourPortail('ecole-a-suspendre');

    $component = Livewire::actingAs($superAdmin)
        ->test(EcoleDetail::class, ['etablissement' => $etablissement])
        ->call('confirmSuspend')
        ->assertSet('confirmingSuspend', true)
        ->set('motifSuspension', 'Impayé')
        ->call('suspendre')
        ->assertSet('confirmingSuspend', false);

    expect($etablissement->fresh()->statut)->toBe(Etablissement::STATUT_SUSPENDU);

    $component->call('reactiver');

    expect($etablissement->fresh()->statut)->toBe(Etablissement::STATUT_ACTIF);
});

it('refuse de suspendre sans motif', function () {
    $superAdmin = creerSuperAdminPourPortail();
    $etablissement = creerEtablissementPourPortail('ecole-sans-motif');

    Livewire::actingAs($superAdmin)
        ->test(EcoleDetail::class, ['etablissement' => $etablissement])
        ->set('motifSuspension', '')
        ->call('suspendre')
        ->assertHasErrors(['motifSuspension' => 'required']);

    expect($etablissement->fresh()->statut)->toBe(Etablissement::STATUT_ACTIF);
});

// --- Onboarding ------------------------------------------------------------

it('crée une école au bout du wizard d’onboarding en 5 étapes', function () {
    Notification::fake();
    $superAdmin = creerSuperAdminPourPortail();
    $plan = PlanTarifaire::create(['nom' => 'Starter', 'prix_mensuel' => 15000, 'actif' => true]);

    Livewire::actingAs($superAdmin)
        ->test(Onboarding::class)
        ->assertSet('step', 1)
        ->set('nom', 'Collège Nelson Mandela')
        ->set('email_directeur', 'directeur@mandela.test')
        ->set('plan_id', $plan->id)
        ->call('etapeSuivante')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->call('terminer')
        ->assertSet('step', 5)
        ->assertHasNoErrors();

    $etablissement = Etablissement::where('nom', 'Collège Nelson Mandela')->firstOrFail();

    expect($etablissement->plan_id)->toBe($plan->id)
        ->and($etablissement->statut)->toBe(Etablissement::STATUT_ACTIF);

    $directeur = User::where('email', 'directeur@mandela.test')->firstOrFail();
    expect($directeur->tenant_id)->toBe($etablissement->tenant_id);
});

it('bloque le passage à l’étape 2 sans les champs obligatoires', function () {
    $superAdmin = creerSuperAdminPourPortail();

    Livewire::actingAs($superAdmin)
        ->test(Onboarding::class)
        ->set('nom', '')
        ->set('email_directeur', 'pas-un-email')
        ->call('etapeSuivante')
        ->assertHasErrors(['nom' => 'required', 'email_directeur' => 'email'])
        ->assertSet('step', 1);
});

// --- AnalyticsDashboard ------------------------------------------------

it('calcule et rafraîchit les indicateurs business', function () {
    $superAdmin = creerSuperAdminPourPortail();
    $plan = PlanTarifaire::create(['nom' => 'Standard', 'prix_mensuel' => 35000, 'actif' => true]);
    creerEtablissementPourPortail('ecole-payante')->update(['plan_id' => $plan->id]);

    Livewire::actingAs($superAdmin)
        ->test(AnalyticsDashboard::class)
        ->assertSet('stats.mrr', 35000.0)
        ->assertSet('stats.nb_ecoles_actives', 1)
        ->call('actualiser')
        ->assertDispatched('analytics-refreshed');
});

// --- Communication -------------------------------------------------------

it('envoie une annonce aux directeurs de toutes les écoles actives et suspendues et journalise l’envoi', function () {
    Notification::fake();
    $superAdmin = creerSuperAdminPourPortail();

    $ecoleActive = creerEtablissementPourPortail('ecole-active-comm');
    $directeurActif = User::factory()->create(['tenant_id' => $ecoleActive->tenant_id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($ecoleActive->id);
    Role::create(['name' => 'ECOLE_ADMIN', 'guard_name' => 'web', 'team_id' => $ecoleActive->id]);
    $directeurActif->assignRole('ECOLE_ADMIN');

    $ecoleArchivee = creerEtablissementPourPortail('ecole-archivee-comm', Etablissement::STATUT_ARCHIVE);
    $directeurArchive = User::factory()->create(['tenant_id' => $ecoleArchivee->tenant_id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($ecoleArchivee->id);
    Role::create(['name' => 'ECOLE_ADMIN', 'guard_name' => 'web', 'team_id' => $ecoleArchivee->id]);
    $directeurArchive->assignRole('ECOLE_ADMIN');

    Livewire::actingAs($superAdmin)
        ->test(Communication::class)
        ->set('sujet', 'Maintenance planifiée')
        ->set('contenu', 'La plateforme sera indisponible dimanche de 2h à 4h.')
        ->call('envoyer')
        ->assertHasNoErrors();

    Notification::assertSentTo($directeurActif, AnnonceGlobale::class);
    Notification::assertNotSentTo($directeurArchive, AnnonceGlobale::class);

    expect(CommunicationLog::where('sujet', 'Maintenance planifiée')->exists())->toBeTrue();
});

it('refuse d’envoyer une annonce sans sujet ni contenu', function () {
    $superAdmin = creerSuperAdminPourPortail();

    Livewire::actingAs($superAdmin)
        ->test(Communication::class)
        ->set('sujet', '')
        ->set('contenu', '')
        ->call('envoyer')
        ->assertHasErrors(['sujet' => 'required', 'contenu' => 'required']);

    expect(CommunicationLog::count())->toBe(0);
});

// --- Montage des pages full-page sous admin.plateforme.sn -----------------

it('sert les 5 pages Livewire du portail à un SUPER_ADMIN authentifié', function () {
    $superAdmin = creerSuperAdminPourPortail();
    $etablissement = creerEtablissementPourPortail('ecole-page-detail');

    $this->actingAs($superAdmin)->get('http://admin.plateforme.sn.localhost/ecoles')->assertOk();
    $this->actingAs($superAdmin)->get('http://admin.plateforme.sn.localhost/ecoles/'.$etablissement->id)->assertOk();
    $this->actingAs($superAdmin)->get('http://admin.plateforme.sn.localhost/onboarding')->assertOk();
    $this->actingAs($superAdmin)->get('http://admin.plateforme.sn.localhost/analytics')->assertOk();
    $this->actingAs($superAdmin)->get('http://admin.plateforme.sn.localhost/communication')->assertOk();
});

// --- Régression : Fortify (login/logout) partagé entre les deux domaines --

/**
 * Avant ce correctif, config/fortify.php forçait `resolve.tenant` sur TOUTES
 * ses routes (domain => null, donc partagées) : /login et /logout sur
 * admin.plateforme.sn faisaient un firstOrFail() sur une "école" admin
 * inexistante avant même d'atteindre Fortify — cf. ResolveTenantOrPlatformTeam.
 */
it('permet à un SUPER_ADMIN de se déconnecter depuis le portail admin sans 404', function () {
    $superAdmin = creerSuperAdminPourPortail();

    $this->actingAs($superAdmin)
        ->post('http://admin.plateforme.sn.localhost/logout')
        ->assertRedirect();

    $this->assertGuest();
});

it('continue de permettre à un utilisateur école de se déconnecter depuis son sous-domaine', function () {
    $etablissement = creerEtablissementPourPortail('ecole-logout');
    $utilisateur = User::factory()->create(['tenant_id' => $etablissement->tenant_id]);

    $this->actingAs($utilisateur)
        ->post('http://ecole-logout.plateforme.sn.localhost/logout')
        ->assertRedirect();

    $this->assertGuest();
});
