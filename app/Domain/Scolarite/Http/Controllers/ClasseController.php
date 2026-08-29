<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Http\Controllers;

use App\Domain\Scolarite\Http\Requests\StoreClasseRequest;
use App\Domain\Scolarite\Http\Requests\UpdateClasseRequest;
use App\Domain\Scolarite\Http\Resources\ClasseResource;
use App\Domain\Scolarite\Models\Classe;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClasseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Classe::class);

        $classes = Classe::with(['niveau', 'anneeScolaire'])->paginate();

        return ClasseResource::collection($classes);
    }

    public function store(StoreClasseRequest $request)
    {
        $classe = Classe::create($request->validated());

        return ClasseResource::make($classe)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Classe $classe)
    {
        $this->authorize('view', $classe);

        return ClasseResource::make($classe->load(['niveau', 'anneeScolaire']));
    }

    public function update(UpdateClasseRequest $request, Classe $classe)
    {
        $classe->update($request->validated());

        return ClasseResource::make($classe);
    }

    public function destroy(Classe $classe)
    {
        $this->authorize('delete', $classe);

        $classe->delete();

        return response()->noContent();
    }
}
