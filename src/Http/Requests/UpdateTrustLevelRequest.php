<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Requests;

use Azuriom\Plugin\InspiratoStats\Models\TrustLevel;
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
