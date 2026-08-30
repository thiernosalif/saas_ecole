<?php

declare(strict_types=1);

namespace App\Domain\Planning\Http\Controllers;

use App\Domain\Planning\Http\Requests\StoreExamenRequest;
use App\Domain\Planning\Http\Requests\UpdateExamenRequest;
use App\Domain\Planning\Http\Resources\ExamenResource;
use App\Domain\Planning\Models\Examen;
use App\Domain\Planning\Services\ExamenService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExamenController extends Controller
{
    public function __construct(private readonly ExamenService $examens) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Examen::class);

        $request->validate(['classe_id' => ['sometimes', 'uuid']]);

        $examens = Examen::query()
            ->when($request->filled('classe_id'), fn ($q) => $q->where('classe_id', $request->string('classe_id')->toString()))
            ->orderBy('date_examen')
            ->get();

        return ExamenResource::collection($examens);
    }

    public function show(Examen $examen)
    {
        $this->authorize('view', $examen);

        return ExamenResource::make($examen);
    }

    public function store(StoreExamenRequest $request)
    {
        $examen = $this->examens->create($request->validated());

        return ExamenResource::make($examen)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateExamenRequest $request, Examen $examen)
    {
        $examen = $this->examens->update($examen, $request->validated());

        return ExamenResource::make($examen);
    }
}
