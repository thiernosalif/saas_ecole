<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Http\Requests;

use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransfertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', Inscription::class);
    }

    public function rules(): array
    {
        return [
            'inscription_id' => ['required', 'uuid', 'exists:inscription,id'],
            'type_sortie' => ['required', 'string', 'in:TRANSFERT,RADIATION,ABANDON'],
            'motif' => ['nullable', 'string'],
            'date_sortie' => ['required', 'date'],
        ];
    }
}
