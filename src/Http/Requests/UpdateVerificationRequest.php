<?php

namespace Azuriom\Plugin\SocialProfile\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:unverified,pending,verified,rejected'],
            'method' => ['nullable', 'string', 'max:255'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
