<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Http\Controllers;

use App\Domain\Comptabilite\Models\Facture;
use App\Domain\Comptabilite\Models\Reglement;
use App\Domain\Comptabilite\Services\Paiement\WebhookSignatureVerifier;
use App\Domain\Comptabilite\Services\ReglementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Hors du groupe `resolve.tenant` (routes/api.php) : Wave/Orange Money ne
 * sont pas des tenants résolus par sous-domaine, ils appellent l'API
 * directement. Le tenant du règlement est déduit de la `reference`, générée
 * côté plateforme à l'initiation (PaiementService), jamais du provider.
 */
class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookSignatureVerifier $signatures,
        private readonly ReglementService $reglements,
    ) {}

    public function wave(Request $request)
    {
        abort_unless(
            $this->signatures->verifie(
                (string) config('services.wave.webhook_secret'),
                $request->getContent(),
                $request->header('X-Wave-Signature'),
            ),
            401,
            'Signature Wave invalide.'
        );

        $succes = $request->input('checkout_status') === 'complete';

        return $this->traiter($request->input('client_reference'), $succes);
    }

    public function orangeMoney(Request $request)
    {
        abort_unless(
            $this->signatures->verifie(
                (string) config('services.orange_money.webhook_secret'),
                $request->getContent(),
                $request->header('X-Orange-Signature'),
            ),
            401,
            'Signature Orange Money invalide.'
        );

        $succes = $request->input('status') === 'SUCCESS';

        return $this->traiter($request->input('order_id'), $succes);
    }

    /**
     * Idempotent par construction : le Reglement existe déjà (EN_ATTENTE)
     * depuis l'initiation, le webhook ne fait qu'une transition d'état sous
     * verrou de ligne. Un rejeu (même reference, déjà CONFIRME/ECHEC) ou une
     * reference inconnue retourne 200 sans rien modifier — jamais de 404,
     * pour ne pas déclencher de boucle de retry côté provider.
     */
    private function traiter(?string $reference, bool $succes): Response
    {
        if (! $reference) {
            return response()->noContent();
        }

        DB::transaction(function () use ($reference, $succes) {
            $reglement = Reglement::withoutTenant()->lockForUpdate()
                ->where('reference', $reference)
                ->first();

            if (! $reglement || $reglement->statut !== Reglement::STATUT_EN_ATTENTE) {
                return;
            }

            if (! $succes) {
                $reglement->update(['statut' => Reglement::STATUT_ECHEC]);

                return;
            }

            $reglement->update(['statut' => Reglement::STATUT_CONFIRME, 'paid_at' => now()]);
            $facture = Facture::withoutTenant()->findOrFail($reglement->facture_id);
            $this->reglements->appliquerAuSolde($facture, $reglement);
        });

        return response()->noContent();
    }
}
