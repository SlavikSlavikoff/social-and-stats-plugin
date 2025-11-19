<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimelineCardOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('social.timelines.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:socialprofile_timeline_cards,id'],
            'items.*.position' => ['required', 'integer', 'min:0'],
            'items.*.period_id' => ['required', 'integer', 'exists:socialprofile_timeline_periods,id'],
        ];
    }
}
