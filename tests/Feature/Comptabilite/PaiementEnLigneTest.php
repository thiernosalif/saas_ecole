<?php

declare(strict_types=1);

use App\Domain\Comptabilite\Models\Facture;
use App\Domain\Comptabilite\Models\Reglement;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Etablissement::create([
        'tenant_id' => 'ecole-paiement-test', 'nom' => 'École Paiement Test', 'sous_domaine' => 'ecole-paiement-test', 'statut' => 'ACTIF',
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->ecole->id);

    $this->eleve = Personne::withoutTenant()->create([
        'tenant_id' => 'ecole-paiement-test', 'type' => Personne::TYPE_ELEVE, 'nom' => 'Diop', 'prenom' => 'Awa', 'num_acte_naissance' => 'P-001',
    ]);

    $this->facture = Facture::withoutTenant()->create([
        'tenant_id' => 'ecole-paiement-test',
        'eleve_id' => $this->eleve->id,
        'numero' => 'FA-2026-002',
        'montant_total' => 50000,
        'montant_paye' => 0,
        'statut' => Facture::STATUT_EN_ATTENTE,
    ]);

    $this->admin = User::factory()->create(['tenant_id' => 'ecole-paiement-test']);
    Role::firstOrCreate(['name' => 'ECOLE_ADMIN', 'guard_name' => 'web', 'team_id' => $this->ecole->id]);
    $this->admin->assignRole('ECOLE_ADMIN');

    config(['services.wave.webhook_secret' => 'secret-test-wave']);
});

function urlPaiement(string $path): string
{
    return "http://ecole-paiement-test.plateforme.sn.localhost{$path}";
}

it('bloque l’initiation d’un paiement en ligne si le module n’est pas actif (403, pas 404/500)', function () {
    // modules_actifs n'a jamais été renseigné sur $this->ecole : le module
    // paiement_mobile_money est donc inactif par défaut.
    Sanctum::actingAs($this->admin, ['*']);

    $this->postJson(urlPaiement("/api/v1/factures/{$this->facture->id}/paiement-mobile"), [
        'moyen_paiement' => Reglement::MOYEN_WAVE,
    ])->assertForbidden();
});

it('permet l’initiation quand le module est actif, puis un webhook Wave valide confirme le paiement une seule fois même rejoué', function () {
    $this->ecole->update(['modules_actifs' => [Etablissement::MODULE_PAIEMENT_MOBILE_MONEY]]);
    Http::fake(['api.wave.com/*' => Http::response(['wave_launch_url' => 'https://pay.wave.com/xyz'], 200)]);

    Sanctum::actingAs($this->admin, ['*']);

    $init = $this->postJson(urlPaiement("/api/v1/factures/{$this->facture->id}/paiement-mobile"), [
        'moyen_paiement' => Reglement::MOYEN_WAVE,
    ])->assertCreated()
        ->assertJsonPath('data.statut', Reglement::STATUT_EN_ATTENTE)
        ->assertJsonPath('url_paiement', 'https://pay.wave.com/xyz');

    $reference = $init->json('data.reference');
    expect(Reglement::withoutTenant()->where('reference', $reference)->first()->statut)->toBe(Reglement::STATUT_EN_ATTENTE);

    $payload = json_encode(['client_reference' => $reference, 'checkout_status' => 'complete']);
    $signature = hash_hmac('sha256', $payload, 'secret-test-wave');

    // Premier webhook : confirme le paiement et crédite la facture.
    $this->call('POST', '/api/webhooks/wave', [], [], [], [
        'HTTP_X_WAVE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $payload)->assertNoContent();

    $this->facture->refresh();
    expect((string) $this->facture->montant_paye)->toBe('50000.00')
        ->and($this->facture->statut)->toBe(Facture::STATUT_PAYEE)
        ->and(Reglement::withoutTenant()->where('reference', $reference)->first()->statut)->toBe(Reglement::STATUT_CONFIRME);

    // Rejeu du même webhook (même payload/signature) : ne doit pas créditer
    // une seconde fois ni créer un second règlement.
    $this->call('POST', '/api/webhooks/wave', [], [], [], [
        'HTTP_X_WAVE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $payload)->assertNoContent();

    $this->facture->refresh();
    expect((string) $this->facture->montant_paye)->toBe('50000.00')
        ->and(Reglement::withoutTenant()->where('facture_id', $this->facture->id)->count())->toBe(1)
        ->and(\App\Domain\Comptabilite\Models\EcritureComptable::where('facture_id', $this->facture->id)->count())->toBe(1);
});

it('rejette un webhook Wave à la signature invalide', function () {
    $payload = json_encode(['client_reference' => 'peu-importe', 'checkout_status' => 'complete']);

    $this->call('POST', '/api/webhooks/wave', [], [], [], [
        'HTTP_X_WAVE_SIGNATURE' => 'signature-incorrecte',
        'CONTENT_TYPE' => 'application/json',
    ], $payload)->assertUnauthorized();
});

it('rejette un webhook Wave sans signature du tout', function () {
    $payload = json_encode(['client_reference' => 'peu-importe', 'checkout_status' => 'complete']);

    $this->call('POST', '/api/webhooks/wave', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $payload)->assertUnauthorized();
});
