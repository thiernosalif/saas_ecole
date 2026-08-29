<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Http\Controllers;

use App\Domain\Scolarite\Http\Requests\StoreEleveRequest;
use App\Domain\Scolarite\Http\Requests\UpdateEleveRequest;
use App\Domain\Scolarite\Http\Resources\EleveResource;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Services\EleveService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EleveController extends Controller
{
    public function __construct(private readonly EleveService $eleves) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Personne::class);

        $eleves = Personne::eleves()
            ->with('inscriptions.classe')
            ->paginate();

        return EleveResource::collection($eleves);
    }

    public function store(StoreEleveRequest $request)
    {
        $eleve = $this->eleves->create($request->validated());

        return EleveResource::make($eleve)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Personne $eleve)
    {
        $this->authorize('view', $eleve);

        return EleveResource::make($eleve->load('inscriptions.classe'));
    }

    public function update(UpdateEleveRequest $request, Personne $eleve)
    {
        $eleve = $this->eleves->update($eleve, $request->validated());

        return EleveResource::make($eleve);
    }

    public function destroy(Personne $eleve)
    {
        $this->authorize('delete', $eleve);

        $this->eleves->delete($eleve);

        return response()->noContent();
    }
}
