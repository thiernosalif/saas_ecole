<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Http\Controllers;

use App\Domain\Comptabilite\Http\Requests\StoreFactureRequest;
use App\Domain\Comptabilite\Http\Resources\FactureResource;
use App\Domain\Comptabilite\Models\Facture;
use App\Domain\Comptabilite\Services\FactureService;
use App\Domain\Scolarite\Models\Personne;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FactureController extends Controller
{
    public function __construct(private readonly FactureService $factures) {}

    /**
     * `eleve_id` est obligatoire (pas de liste "toutes factures" pour un
     * PARENT/ELEVE) : l'autorisation passe par ElevePolicy::view (déjà
     * exacte pour la propriété élève/parent, cf. §4.4) avant de révéler la
     * moindre facture — plus sûr que de ne vérifier que le rôle.
     */
    public function index(Request $request)
    {
        $request->validate(['eleve_id' => ['required', 'uuid']]);

        $eleve = Personne::eleves()->findOrFail($request->string('eleve_id')->toString());
        $this->authorize('view', $eleve);

        return FactureResource::collection($this->factures->pourEleve($eleve->id));
    }

    public function store(StoreFactureRequest $request)
    {
        $facture = $this->factures->create($request->validated());

        return FactureResource::make($facture)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Facture $facture)
    {
        $this->authorize('view', $facture);

        return FactureResource::make($facture->load('reglements'));
    }
}
