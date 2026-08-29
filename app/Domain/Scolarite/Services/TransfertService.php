<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Services;

use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Support\Facades\DB;

class TransfertService
{
    public function declencher(array $data): Inscription
    {
        return DB::transaction(function () use ($data) {
            $inscription = Inscription::findOrFail($data['inscription_id']);
            $inscription->update(['statut' => $data['type_sortie']]);
            $inscription->eleve->update(['actif' => false]);

            return $inscription->refresh();
        });
    }
}
