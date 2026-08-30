<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Http\Requests;

use App\Domain\Comptabilite\Models\Reglement;
use Illuminate\Foundation\Http\FormRequest;

class InitierPaiementEnLigneRequest extends FormRequest
{
    /**
     * Le payeur doit être propriétaire (ou staff) de la facture concernée —
     * réutilise FacturePolicy::view, déjà correcte pour PARENT/ELEVE (§4.4),
     * plutôt qu'une nouvelle ability dupliquant la même règle.
     */
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('facture'));
    }

    public function rules(): array
    {
        return [
            'moyen_paiement' => ['required', 'string', 'in:'.implode(',', Reglement::MOYENS_EN_LIGNE)],
            'montant' => ['sometimes', 'numeric', 'min:0.01'],
        ];
    }
}
