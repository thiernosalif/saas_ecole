<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Http\Requests;

use App\Domain\SuperAdmin\Models\ReglementSaas;
use Illuminate\Foundation\Http\FormRequest;

class InitierPaiementAbonnementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'moyen_paiement' => ['required', 'string', 'in:'.implode(',', ReglementSaas::MOYENS_EN_LIGNE)],
        ];
    }
}
