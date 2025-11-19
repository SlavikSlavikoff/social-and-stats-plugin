<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AutomationSchedulerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('social.automation.manage') ?? false;
    }

    public function rules(): array
    {
        $sources = ['social_score', 'activity', 'coins'];

        return [
            'enabled' => ['boolean'],
            'day' => ['required', 'integer', 'min:1', 'max:28'],
            'hour' => ['required', 'integer', 'min:0', 'max:23'],
            'top_limit' => ['required', 'integer', 'min:1', 'max:50'],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*' => [Rule::in($sources)],
            'reward' => ['required', 'array'],
            'reward.social_score' => ['required', 'numeric'],
            'reward.coins' => ['required', 'numeric'],
            'reward.activity' => ['required', 'numeric'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'enabled' => $this->boolean('enabled'),
            'sources' => (array) $this->input('sources', []),
            'reward' => [
                'social_score' => $this->input('reward.social_score', 0),
                'coins' => $this->input('reward.coins', 0),
                'activity' => $this->input('reward.activity', 0),
            ],
        ]);
    }
}
