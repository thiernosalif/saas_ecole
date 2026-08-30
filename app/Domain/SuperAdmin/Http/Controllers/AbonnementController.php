<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Http\Controllers;

use App\Domain\SuperAdmin\Http\Requests\ChangerPlanRequest;
use App\Domain\SuperAdmin\Http\Requests\InitierPaiementAbonnementRequest;
use App\Domain\SuperAdmin\Http\Resources\EtablissementResource;
use App\Domain\SuperAdmin\Http\Resources\ReglementSaasResource;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Models\PlanTarifaire;
use App\Domain\SuperAdmin\Services\AbonnementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class AbonnementController extends Controller
{
    public function __construct(private readonly AbonnementService $abonnements) {}

    public function plans()
    {
        return PlanTarifaire::where('actif', true)->orderBy('prix_mensuel')->get();
    }

    public function changerPlan(ChangerPlanRequest $request, Etablissement $etablissement)
    {
        $plan = PlanTarifaire::findOrFail($request->validated('plan_id'));
        $etablissement = $this->abonnements->changerPlan($etablissement, $plan);

        return EtablissementResource::make($etablissement->load('plan'));
    }

    public function annuler(Etablissement $etablissement)
    {
        $etablissement = $this->abonnements->annuler($etablissement);

        return EtablissementResource::make($etablissement->load('plan'));
    }

    /**
     * Génère (ou réutilise) l'échéance du mois courant puis initie le
     * paiement en ligne dessus — le Super Admin déclenche l'un ou l'autre
     * indifféremment depuis le portail (cf. EcoleDetail::payerEnLigne).
     */
    public function payer(InitierPaiementAbonnementRequest $request, Etablissement $etablissement)
    {
        $reglement = $this->abonnements->genererEcheanceCourante($etablissement->load('plan'));
        $resultat = $this->abonnements->initierPaiement($reglement, $request->validated('moyen_paiement'));

        return ReglementSaasResource::make($resultat['reglement'])
            ->additional(['url_paiement' => $resultat['url_paiement']])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function historique(Etablissement $etablissement)
    {
        return ReglementSaasResource::collection($this->abonnements->historique($etablissement));
    }
}
