<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuspendreEtablissementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'max:500'],
        ];
    }
}
