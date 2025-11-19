<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TimelineCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('social.timelines.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'period_id' => ['required', 'integer', 'exists:socialprofile_timeline_periods,id'],
            'title' => ['required', 'string', 'max:191'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'button_label' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'url', 'max:500'],
            'image' => ['nullable', 'image', 'max:4096'],
            'items' => ['required', 'array', 'min:1', 'max:5'],
            'items.*' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'highlight' => ['sometimes', 'boolean'],
            'is_visible' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = collect($this->input('items', []))
                ->filter(static fn ($value) => filled($value));

            if ($items->count() === 0) {
                $validator->errors()->add('items', __('socialprofile::messages.admin.timelines.validation.items_required'));
            }
        });
    }
}
