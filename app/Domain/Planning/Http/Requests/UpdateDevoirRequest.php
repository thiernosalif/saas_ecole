<?php

declare(strict_types=1);

namespace App\Domain\Planning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDevoirRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('devoir'));
    }

    public function rules(): array
    {
        return [
            'prof_id' => ['nullable', 'uuid', 'exists:personne,id'],
            'titre' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'date_remise' => ['sometimes', 'date'],
        ];
    }
}
