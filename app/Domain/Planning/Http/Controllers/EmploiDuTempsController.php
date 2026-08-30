<?php

declare(strict_types=1);

namespace App\Domain\Planning\Http\Controllers;

use App\Domain\Planning\Http\Requests\StoreSeanceRequest;
use App\Domain\Planning\Http\Requests\UpdateSeanceRequest;
use App\Domain\Planning\Http\Resources\SeanceResource;
use App\Domain\Planning\Models\Seance;
use App\Domain\Planning\Services\SeanceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmploiDuTempsController extends Controller
{
    public function __construct(private readonly SeanceService $seances) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Seance::class);

        $request->validate([
            'classe_id' => ['sometimes', 'uuid'],
            'prof_id' => ['sometimes', 'uuid'],
        ]);

        $seances = Seance::query()
            ->when($request->filled('classe_id'), fn ($q) => $q->where('classe_id', $request->string('classe_id')->toString()))
            ->when($request->filled('prof_id'), fn ($q) => $q->where('prof_id', $request->string('prof_id')->toString()))
            ->orderBy('jour_semaine')
            ->orderBy('heure_debut')
            ->get();

        return SeanceResource::collection($seances);
    }

    public function show(Seance $seance)
    {
        $this->authorize('view', $seance);

        return SeanceResource::make($seance);
    }

    public function store(StoreSeanceRequest $request)
    {
        $seance = $this->seances->create($request->validated());

        return SeanceResource::make($seance)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateSeanceRequest $request, Seance $seance)
    {
        $seance = $this->seances->deplacer($seance, $request->validated());

        return SeanceResource::make($seance);
    }
}
