<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Http\Controllers;

use App\Domain\Scolarite\Http\Requests\StoreAbsenceRequest;
use App\Domain\Scolarite\Http\Resources\AbsenceResource;
use App\Domain\Scolarite\Models\Absence;
use App\Domain\Scolarite\Services\AbsenceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AbsenceController extends Controller
{
    public function __construct(private readonly AbsenceService $absences) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Absence::class);

        $request->validate(['eleve_id' => ['required', 'uuid']]);

        return AbsenceResource::collection($this->absences->pourEleve($request->string('eleve_id')->toString()));
    }

    public function store(StoreAbsenceRequest $request)
    {
        $absence = $this->absences->create($request->validated(), $request->user()->personne_id);

        return AbsenceResource::make($absence)->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
