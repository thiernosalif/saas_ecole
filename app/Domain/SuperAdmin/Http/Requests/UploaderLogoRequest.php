<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploaderLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];
    }
}
