<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Services;

use App\Domain\Scolarite\Models\Personne;
use Illuminate\Support\Facades\DB;

class EleveService
{
    public function create(array $data): Personne
    {
        return DB::transaction(fn () => Personne::create([
            ...$data,
            'type' => Personne::TYPE_ELEVE,
        ]));
    }

    public function update(Personne $eleve, array $data): Personne
    {
        DB::transaction(function () use ($eleve, $data) {
            $eleve->update($data);
        });

        return $eleve->refresh();
    }

    public function delete(Personne $eleve): void
    {
        DB::transaction(fn () => $eleve->delete());
    }
}
