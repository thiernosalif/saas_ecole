<?php

declare(strict_types=1);

namespace App\Domain\Notes\Services;

use App\Domain\Notes\Models\Note;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class NoteService
{
    public function create(array $data, ?string $saisiePar): Note
    {
        return DB::transaction(fn () => Note::create([
            ...$data,
            'saisie_par' => $data['saisie_par'] ?? $saisiePar,
        ]));
    }

    public function pourEleve(string $eleveId, ?string $matiereId = null, ?string $trimestreId = null): Collection
    {
        return Note::where('eleve_id', $eleveId)
            ->when($matiereId, fn ($query) => $query->where('matiere_id', $matiereId))
            ->when($trimestreId, fn ($query) => $query->where('trimestre_id', $trimestreId))
            ->orderByDesc('created_at')
            ->get();
    }
}
