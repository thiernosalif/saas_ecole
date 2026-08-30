<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Http\Controllers;

use App\Domain\Scolarite\Http\Requests\StoreCompteUtilisateurRequest;
use App\Domain\Scolarite\Http\Resources\CompteUtilisateurResource;
use App\Domain\Scolarite\Services\CompteUtilisateurService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Response;

class CompteUtilisateurController extends Controller
{
    public function __construct(private readonly CompteUtilisateurService $comptes) {}

    public function index()
    {
        $this->authorize('viewAny', User::class);

        return CompteUtilisateurResource::collection($this->comptes->lister());
    }

    public function store(StoreCompteUtilisateurRequest $request)
    {
        $donnees = $request->validated();

        $compte = $this->comptes->creerCompteStaff($donnees, $donnees['role']);

        return CompteUtilisateurResource::make($compte)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $compte)
    {
        $compte = $this->comptes->trouver($compte);

        $this->authorize('viewAny', User::class);

        return CompteUtilisateurResource::make($compte);
    }

    public function desactiver(string $compte)
    {
        $compte = $this->comptes->trouver($compte);

        $this->authorize('update', $compte);

        $this->comptes->desactiver($compte);

        return response()->noContent();
    }
}
