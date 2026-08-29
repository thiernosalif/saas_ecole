<?php

declare(strict_types=1);

namespace App\Domain\Notes\Http\Requests;

use App\Domain\Notes\Models\Bulletin;
use Illuminate\Foundation\Http\FormRequest;

class GenererBulletinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Bulletin::class);
    }

    public function rules(): array
    {
        return [
            'trimestre_id' => ['required', 'uuid', 'exists:trimestre,id'],
            'classe_id' => ['required_without:eleve_id', 'nullable', 'uuid', 'exists:classe,id'],
            'eleve_id' => ['required_without:classe_id', 'nullable', 'uuid', 'exists:personne,id'],
        ];
    }
}
