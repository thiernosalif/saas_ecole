<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Http\Controllers;

use App\Domain\Comptabilite\Services\Paiement\WebhookSignatureVerifier;
use App\Domain\SuperAdmin\Models\ReglementSaas;
use App\Domain\SuperAdmin\Notifications\PaiementAbonnementEchoue;
use App\Domain\SuperAdmin\Services\AccesService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Webhooks Wave/Orange Money pour l'abonnement plateforme d'une école
 * (Session 13) — distinct de Comptabilite\Http\Controllers\WebhookController
 * qui traite les règlements des parents (table `reglement`, pas
 * `reglement_saas`) : deux tables, deux points d'entrée, la `reference`
 * générée à l'initiation ne suffit pas à elle seule à choisir la bonne table.
 * `WebhookSignatureVerifier` est réutilisé tel quel (utilitaire HMAC
 * générique, aucun couplage métier Comptabilite).
 */
class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookSignatureVerifier $signatures,
        private readonly AccesService $acces,
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
     * Idempotent comme son équivalent Comptabilite : la ligne existe déjà
     * EN_ATTENTE depuis l'initiation, un rejeu ou une reference inconnue
     * retourne 200 sans rien modifier. Différence volontaire sur l'échec :
     * on NE passe PAS le statut à un état terminal — la ligne doit rester
     * EN_ATTENTE pour que CheckAbonnementsCommand continue de la surveiller
     * (cf. commentaire sur ReglementSaas et le Context du plan de session).
     */
    private function traiter(?string $reference, bool $succes): Response
    {
        if (! $reference) {
            return response()->noContent();
        }

        DB::transaction(function () use ($reference, $succes) {
            $reglement = ReglementSaas::with('etablissement')
                ->lockForUpdate()
                ->where('reference', $reference)
                ->first();

            if (! $reglement || $reglement->statut !== ReglementSaas::STATUT_EN_ATTENTE) {
                return;
            }

            if (! $succes) {
                if ($reglement->etablissement) {
                    $this->acces->notifierDirecteurs(
                        $reglement->etablissement,
                        new PaiementAbonnementEchoue($reglement->etablissement->nom),
                    );
                }

                return;
            }

            $reglement->update(['statut' => ReglementSaas::STATUT_PAYE, 'paid_at' => now()]);
        });

        return response()->noContent();
    }
}
