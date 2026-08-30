<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Http\Controllers;

use App\Domain\Comptabilite\Http\Requests\InitierPaiementEnLigneRequest;
use App\Domain\Comptabilite\Http\Requests\StoreReglementManuelRequest;
use App\Domain\Comptabilite\Http\Resources\ReglementResource;
use App\Domain\Comptabilite\Models\Facture;
use App\Domain\Comptabilite\Models\Reglement;
use App\Domain\Comptabilite\Services\Paiement\PaiementService;
use App\Domain\Comptabilite\Services\ReglementService;
use App\Http\Controllers\Controller;

class ReglementController extends Controller
{
    public function __construct(
        private readonly ReglementService $reglements,
        private readonly PaiementService $paiements,
    ) {}

    public function index(Facture $facture)
    {
        $this->authorize('view', $facture);

        return ReglementResource::collection($facture->reglements()->orderByDesc('paid_at')->get());
    }

    public function show(Reglement $reglement)
    {
        $this->authorize('view', $reglement);

        return ReglementResource::make($reglement);
    }

    /**
     * Enregistrement manuel (espèces/chèque/virement) uniquement — le
     * paiement en ligne a son propre point d'entrée, gardé derrière le
     * module `paiement_mobile_money` (cf. PaiementService).
     */
    public function store(StoreReglementManuelRequest $request, Facture $facture)
    {
        $reglement = $this->reglements->enregistrerPaiementManuel($facture, $request->validated());

        return ReglementResource::make($reglement)->response()->setStatusCode(201);
    }

    /**
     * Initiation d'un paiement en ligne (Wave/Orange Money) — route gardée
     * par le middleware `module:paiement_mobile_money` (routes/api.php),
     * l'école doit avoir activé le module. La confirmation arrive plus tard
     * via WebhookController, pas ici.
     */
    public function payerEnLigne(InitierPaiementEnLigneRequest $request, Facture $facture)
    {
        $resultat = $this->paiements->initier(
            $facture,
            $request->validated('moyen_paiement'),
            $request->validated(),
        );

        return ReglementResource::make($resultat['reglement'])
            ->additional(['url_paiement' => $resultat['url_paiement']])
            ->response()
            ->setStatusCode(201);
    }
}
