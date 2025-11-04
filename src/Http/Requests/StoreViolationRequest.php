<?php

namespace Azuriom\Plugin\SocialProfile\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreViolationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'type' => ['required', 'in:warning,mute,ban,other'],
            'reason' => ['required', 'string', 'max:5000'],
            'points' => ['required', 'integer', 'min:0'],
            'evidence_url' => ['nullable', 'url'],
        ];
    }
}
