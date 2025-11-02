<?php

namespace Azuriom\Plugin\SocialProfile\Http\Requests;

use Azuriom\Plugin\SocialProfile\Models\TrustLevel;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTrustLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level' => ['required', 'in:'.implode(',', TrustLevel::LEVELS)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
