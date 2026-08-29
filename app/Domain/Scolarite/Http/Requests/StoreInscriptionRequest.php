<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Http\Requests;

use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Foundation\Http\FormRequest;

class StoreInscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Inscription::class);
    }

    public function rules(): array
    {
        return [
            'eleve_id' => ['required', 'uuid', 'exists:personne,id'],
            'classe_id' => ['required', 'uuid', 'exists:classe,id'],
            'annee_id' => ['required', 'uuid', 'exists:annee_scolaire,id'],
            'date_inscription' => ['required', 'date'],
            'frais_inscription' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
