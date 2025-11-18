<?php

namespace Azuriom\Plugin\SocialProfile\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'played_minutes' => ['required', 'integer', 'min:0'],
            'kills' => ['nullable', 'integer', 'min:0'],
            'deaths' => ['nullable', 'integer', 'min:0'],
            'extra_metrics' => ['nullable', 'array'],
        ];
    }
}
