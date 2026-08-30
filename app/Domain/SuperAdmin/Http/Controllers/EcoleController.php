<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Http\Controllers;

use App\Domain\SuperAdmin\Http\Requests\StoreEtablissementRequest;
use App\Domain\SuperAdmin\Http\Requests\UpdateEtablissementRequest;
use App\Domain\SuperAdmin\Http\Resources\EtablissementResource;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Services\EcoleService;
use App\Domain\SuperAdmin\Services\OnboardingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class EcoleController extends Controller
{
    public function __construct(
        private readonly EcoleService $ecoles,
        private readonly OnboardingService $onboarding,
    ) {}

    public function index()
    {
        $etablissements = Etablissement::with('plan')->latest('created_at')->paginate();

        return EtablissementResource::collection($etablissements);
    }

    public function store(StoreEtablissementRequest $request)
    {
        $resultat = $this->onboarding->onboarder(
            $request->validated(),
            $request->file('logo'),
        );

        return response()->json([
            'etablissement' => EtablissementResource::make($resultat['etablissement']),
            'recapitulatif' => $resultat['recapitulatif'],
        ], Response::HTTP_CREATED);
    }

    public function show(Etablissement $etablissement)
    {
        return EtablissementResource::make($etablissement->load('plan'));
    }

    public function update(UpdateEtablissementRequest $request, Etablissement $etablissement)
    {
        $etablissement = $this->ecoles->mettreAJour($etablissement, $request->validated());

        return EtablissementResource::make($etablissement);
    }

    public function destroy(Etablissement $etablissement)
    {
        $this->ecoles->supprimer($etablissement);

        return response()->noContent();
    }
}
