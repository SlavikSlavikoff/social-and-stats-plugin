<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AutomationIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('social.automation.manage') ?? false;
    }

    public function rules(): array
    {
        $types = [
            'minecraft_rcon',
            'minecraft_db',
            'social_bot',
        ];

        return [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in($types)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_default' => ['boolean'],
            'config' => ['array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $config = Arr::wrap($this->input('config', []));

        if (isset($config['default_headers_json']) && $config['default_headers_json'] !== '') {
            $headers = json_decode($config['default_headers_json'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw ValidationException::withMessages([
                    'config.default_headers_json' => __('socialprofile::messages.admin.automation.integrations.headers_error'),
                ]);
            }

            $config['default_headers'] = $headers;
        }

        unset($config['default_headers_json']);

        $this->merge([
            'is_default' => $this->boolean('is_default'),
            'config' => $config,
        ]);
    }
}
