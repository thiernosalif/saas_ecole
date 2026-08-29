<?php

declare(strict_types=1);

namespace App\Domain\Notes\Http\Controllers;

use App\Domain\Notes\Http\Requests\StoreNoteRequest;
use App\Domain\Notes\Http\Resources\NoteResource;
use App\Domain\Notes\Models\Note;
use App\Domain\Notes\Services\NoteService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NoteController extends Controller
{
    public function __construct(private readonly NoteService $notes) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Note::class);

        $request->validate([
            'eleve_id' => ['required', 'uuid'],
            'matiere_id' => ['sometimes', 'uuid'],
            'trimestre_id' => ['sometimes', 'uuid'],
        ]);

        $notes = $this->notes->pourEleve(
            $request->string('eleve_id')->toString(),
            $request->string('matiere_id')->toString() ?: null,
            $request->string('trimestre_id')->toString() ?: null,
        );

        return NoteResource::collection($notes);
    }

    public function store(StoreNoteRequest $request)
    {
        $note = $this->notes->create($request->validated(), $request->user()->personne_id);

        return NoteResource::make($note)->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
