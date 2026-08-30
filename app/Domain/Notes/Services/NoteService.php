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
        // tenant_id n'est jamais accepté depuis l'appelant : seul le
        // TenantScope/BelongsToTenant (via currentTenantId) doit le fixer,
        // sinon un tenant_id injecté dans $data écraserait le tenant courant.
        unset($data['tenant_id']);

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
