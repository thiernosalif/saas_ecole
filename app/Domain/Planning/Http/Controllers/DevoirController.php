<?php

declare(strict_types=1);

namespace App\Domain\Planning\Http\Controllers;

use App\Domain\Planning\Http\Requests\StoreDevoirRequest;
use App\Domain\Planning\Http\Requests\UpdateDevoirRequest;
use App\Domain\Planning\Http\Resources\DevoirResource;
use App\Domain\Planning\Models\Devoir;
use App\Domain\Planning\Services\DevoirService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DevoirController extends Controller
{
    public function __construct(private readonly DevoirService $devoirs) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Devoir::class);

        $request->validate(['classe_id' => ['sometimes', 'uuid']]);

        $devoirs = Devoir::query()
            ->when($request->filled('classe_id'), fn ($q) => $q->where('classe_id', $request->string('classe_id')->toString()))
            ->orderBy('date_remise')
            ->get();

        return DevoirResource::collection($devoirs);
    }

    public function show(Devoir $devoir)
    {
        $this->authorize('view', $devoir);

        return DevoirResource::make($devoir);
    }

    public function store(StoreDevoirRequest $request)
    {
        $devoir = $this->devoirs->create($request->validated());

        return DevoirResource::make($devoir)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateDevoirRequest $request, Devoir $devoir)
    {
        $devoir = $this->devoirs->update($devoir, $request->validated());

        return DevoirResource::make($devoir);
    }
}
