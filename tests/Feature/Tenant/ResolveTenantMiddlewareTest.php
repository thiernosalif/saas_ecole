<?php

declare(strict_types=1);

use App\Domain\SuperAdmin\Models\Etablissement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware('resolve.tenant')->get('/__test/resolve-tenant', function () {
        return response()->json(['tenant_id' => app('currentTenantId')]);
    });
});

// Le client de test construit l'URL de requête via url() (config('app.url')), qui
// écrase tout header Host passé en argument (Symfony Request::create() dérive
// HTTP_HOST de l'hôte présent dans l'URI). On passe donc une URL absolue avec le
// bon sous-domaine directement, plutôt qu'un header Host qui serait ignoré.

it('resolves the tenant from the subdomain and binds it to the container', function () {
    Etablissement::create([
        'tenant_id' => 'ecole-al-iman',
        'nom' => 'École Al Iman',
        'sous_domaine' => 'ecole-al-iman',
        'statut' => 'ACTIF',
    ]);

    $this->get('http://ecole-al-iman.plateforme.sn.localhost/__test/resolve-tenant')
        ->assertOk()
        ->assertJson(['tenant_id' => 'ecole-al-iman']);
});

it('returns 404 for an unknown subdomain', function () {
    $this->get('http://ecole-inconnue.plateforme.sn.localhost/__test/resolve-tenant')
        ->assertNotFound();
});

it('returns 403 when the school is suspended', function () {
    Etablissement::create([
        'tenant_id' => 'ecole-suspendue',
        'nom' => 'École Suspendue',
        'sous_domaine' => 'ecole-suspendue',
        'statut' => 'SUSPENDU',
    ]);

    $this->get('http://ecole-suspendue.plateforme.sn.localhost/__test/resolve-tenant')
        ->assertForbidden();
});
