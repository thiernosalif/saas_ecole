<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Services;

use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Support\Facades\DB;

class InscriptionService
{
    public function create(array $data): Inscription
    {
        return DB::transaction(fn () => Inscription::create([
            ...$data,
            'statut' => Inscription::STATUT_ACTIVE,
        ]));
    }
}
