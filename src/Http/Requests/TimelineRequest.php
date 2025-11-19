<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Requests;

use Azuriom\Plugin\InspiratoStats\Models\Timeline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TimelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('social.timelines.manage') ?? false;
    }

    public function rules(): array
    {
        $timeline = $this->route('timeline');

        $rules = [
            'title' => ['required', 'string', 'max:191'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'intro_text' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'show_period_labels' => ['sometimes', 'boolean'],
        ];

        if ($timeline === null) {
            $rules['type'] = [
                'required',
                Rule::in(Timeline::TYPES),
                Rule::unique('socialprofile_timelines', 'type'),
            ];
        } else {
            $rules['type'] = ['prohibited'];
        }

        return $rules;
    }
}
