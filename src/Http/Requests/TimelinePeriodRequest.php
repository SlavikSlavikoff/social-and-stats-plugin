<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimelinePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('social.timelines.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
