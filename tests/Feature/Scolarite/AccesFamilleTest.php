<?php

declare(strict_types=1);

use App\Domain\Scolarite\Livewire\EleveForm;
use App\Domain\Scolarite\Models\LienParente;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Services\AccesFamilleService;
use App\Domain\Scolarite\Services\CompteUtilisateurService;
use App\Domain\Scolarite\Services\EleveService;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * Session 14 (2/2) : saisie/liaison des parents au moment de l'inscription
 * (fiche élève, cf. EleveForm) et création différée des comptes PARENT/ELEVE
 * — jamais automatique à l'inscription (§14, "flag plutôt qu'une génération
 * systématique").
 */
function creerEcoleAvecAdminFamille(string $slug): array
{
    $etablissement = Etablissement::create([
        'tenant_id' => $slug, 'nom' => "École {$slug}", 'sous_domaine' => $slug, 'statut' => 'ACTIF',
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
    foreach (['ECOLE_ADMIN', 'SCOLARITE', 'PARENT', 'ELEVE'] as $role) {
        Role::create(['name' => $role, 'guard_name' => 'web', 'team_id' => $etablissement->id]);
    }

    $admin = User::factory()->create(['tenant_id' => $slug]);
    $admin->assignRole('ECOLE_ADMIN');

    app()->instance('currentTenant', $etablissement);
    app()->instance('currentTenantId', $etablissement->tenant_id);

    return [$etablissement, $admin];
}

afterEach(function () {
    app()->forgetInstance('currentTenant');
    app()->forgetInstance('currentTenantId');
});

it('crée un accès élève seulement via une action explicite, jamais automatiquement à l’inscription', function () {
    creerEcoleAvecAdminFamille('ecole-a');

    $eleve = app(EleveService::class)->create([
        'nom' => 'Diop', 'prenom' => 'Awa', 'date_naissance' => '2013-01-01',
        'num_acte_naissance' => 'A-001', 'email' => 'awa.diop@ecole-a.test',
    ]);

    expect(User::where('personne_id', $eleve->id)->exists())->toBeFalse();
});

it('lie un nouveau parent à un élève et crée son accès si demandé', function () {
    Notification::fake();
    creerEcoleAvecAdminFamille('ecole-a');

    $eleve = app(EleveService::class)->create([
        'nom' => 'Diop', 'prenom' => 'Awa', 'date_naissance' => '2013-01-01', 'num_acte_naissance' => 'A-001',
    ]);

    app(AccesFamilleService::class)->lierParents($eleve, [[
        'nom' => 'Diop', 'prenom' => 'Modou', 'email' => 'modou.diop@ecole-a.test',
        'lien' => 'PERE', 'tuteur_principal' => true, 'creer_compte' => true,
    ]]);

    $lien = LienParente::where('eleve_id', $eleve->id)->firstOrFail();
    expect($lien->tuteur_principal)->toBeTrue();

    $parent = $lien->parentPersonne;
    expect($parent->type)->toBe(Personne::TYPE_PARENT);

    $user = User::where('personne_id', $parent->id)->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('PARENT'))->toBeTrue();
});

it('réutilise le même parent (retrouvé par email) pour un second enfant sans dupliquer la Personne ni le compte', function () {
    Notification::fake();
    creerEcoleAvecAdminFamille('ecole-a');

    $acces = app(AccesFamilleService::class);
    $eleves = app(EleveService::class);

    $enfant1 = $eleves->create(['nom' => 'Diop', 'prenom' => 'Awa', 'date_naissance' => '2013-01-01', 'num_acte_naissance' => 'A-001']);
    $enfant2 = $eleves->create(['nom' => 'Diop', 'prenom' => 'Fatou', 'date_naissance' => '2015-01-01', 'num_acte_naissance' => 'A-002']);

    $donneesParent = [
        'nom' => 'Diop', 'prenom' => 'Modou', 'email' => 'modou.diop@ecole-a.test', 'creer_compte' => true,
    ];

    $acces->lierParents($enfant1, [$donneesParent]);
    $acces->lierParents($enfant2, [$donneesParent]);

    expect(Personne::where('type', Personne::TYPE_PARENT)->count())->toBe(1);

    $parent = Personne::where('type', Personne::TYPE_PARENT)->firstOrFail();
    expect(User::where('personne_id', $parent->id)->count())->toBe(1)
        ->and(LienParente::where('parent_id', $parent->id)->count())->toBe(2);
});

it('refuse de créer un compte sans email, et n’en crée jamais deux pour la même personne', function () {
    creerEcoleAvecAdminFamille('ecole-a');

    $eleve = app(EleveService::class)->create([
        'nom' => 'Diop', 'prenom' => 'Awa', 'date_naissance' => '2013-01-01', 'num_acte_naissance' => 'A-001',
    ]);

    expect(fn () => app(CompteUtilisateurService::class)->creerCompteEleve($eleve))
        ->toThrow(RuntimeException::class);

    $eleve->update(['email' => 'awa.diop@ecole-a.test']);
    app(CompteUtilisateurService::class)->creerCompteEleve($eleve);

    expect(fn () => app(CompteUtilisateurService::class)->creerCompteEleve($eleve))
        ->toThrow(RuntimeException::class);
});

it('permet à un ECOLE_ADMIN de lier un parent depuis la fiche élève (EleveForm) et bloque la création d’un accès pour un parent non lié (IDOR)', function () {
    Notification::fake();
    [$ecole, $admin] = creerEcoleAvecAdminFamille('ecole-a');

    $eleve = app(EleveService::class)->create([
        'nom' => 'Diop', 'prenom' => 'Awa', 'date_naissance' => '2013-01-01', 'num_acte_naissance' => 'A-001',
    ]);

    $autreEleve = app(EleveService::class)->create([
        'nom' => 'Ba', 'prenom' => 'Moussa', 'date_naissance' => '2013-01-01', 'num_acte_naissance' => 'A-002',
    ]);
    $parentEtranger = Personne::create([
        'type' => Personne::TYPE_PARENT, 'nom' => 'Ba', 'prenom' => 'Fatou', 'email' => 'fatou.ba@ecole-a.test',
    ]);
    LienParente::create(['eleve_id' => $autreEleve->id, 'parent_id' => $parentEtranger->id]);

    Livewire::actingAs($admin);

    Livewire::test(EleveForm::class, ['eleve' => $eleve])
        ->set('nouveauxParents', [[
            'nom' => 'Diop', 'prenom' => 'Modou', 'telephone' => '', 'email' => 'modou.diop@ecole-a.test',
            'lien' => 'PERE', 'tuteur_principal' => true, 'contact_urgence' => false, 'creer_compte' => false,
        ]])
        ->call('save')
        ->assertHasNoErrors();

    expect(LienParente::where('eleve_id', $eleve->id)->count())->toBe(1);

    // Le parent de l'AUTRE élève n'est pas lié à $eleve : la tentative de lui
    // créer un accès depuis la fiche de $eleve doit échouer, pas silencieusement
    // créer un compte pour une personne extérieure à ce dossier.
    expect(fn () => Livewire::test(EleveForm::class, ['eleve' => $eleve])
        ->call('creerAccesParent', $parentEtranger->id))
        ->toThrow(ModelNotFoundException::class);

    expect(User::where('personne_id', $parentEtranger->id)->exists())->toBeFalse();
});
