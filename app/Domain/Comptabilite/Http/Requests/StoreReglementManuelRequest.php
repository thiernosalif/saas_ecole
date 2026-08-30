<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Http\Requests;

use App\Domain\Comptabilite\Models\Reglement;
use Illuminate\Foundation\Http\FormRequest;

class StoreReglementManuelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Reglement::class);
    }

    public function rules(): array
    {
        return [
            'montant' => ['required', 'numeric', 'min:0.01'],
            'moyen_paiement' => ['required', 'string', 'in:'.implode(',', Reglement::MOYENS_MANUELS)],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
