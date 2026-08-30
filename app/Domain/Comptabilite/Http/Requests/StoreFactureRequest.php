<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Http\Requests;

use App\Domain\Comptabilite\Models\Facture;
use Illuminate\Foundation\Http\FormRequest;

class StoreFactureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Facture::class);
    }

    public function rules(): array
    {
        return [
            'eleve_id' => ['required', 'uuid', 'exists:personne,id'],
            'annee_id' => ['nullable', 'uuid', 'exists:annee_scolaire,id'],
            'numero' => ['nullable', 'string', 'max:30'],
            'montant_total' => ['required', 'numeric', 'min:0.01'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
