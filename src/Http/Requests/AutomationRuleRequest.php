<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AutomationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('social.automation.manage') ?? false;
    }

    public function rules(): array
    {
        $triggers = array_keys(config('socialprofile.automation.triggers', []));

        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'trigger_type' => ['required', Rule::in($triggers)],
            'enabled' => ['boolean'],
            'priority' => ['integer'],
            'conditions' => ['array'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', 'string'],
            'actions.*.integration_id' => ['nullable', 'integer', 'exists:socialprofile_automation_integrations,id'],
            'actions.*.config' => ['array'],
            'actions.*.continue_on_error' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();
        $input['enabled'] = $this->boolean('enabled');
        $input['priority'] = (int) ($input['priority'] ?? 0);
        $input['conditions'] = $this->normalizeConditions($input['conditions'] ?? []);
        $input['actions'] = $this->normalizeActions($input['actions'] ?? []);

        $this->replace($input);
    }

    protected function normalizeConditions(array $conditions): array
    {
        $multiValueKeys = [
            'from_roles',
            'to_roles',
            'from_levels',
            'to_levels',
            'metrics',
            'positions',
            'violation_types',
            'case_actions',
            'case_statuses',
            'case_modes',
        ];

        foreach ($multiValueKeys as $key) {
            if (isset($conditions[$key])) {
                $conditions[$key] = array_values(array_filter(
                    (array) $conditions[$key],
                    fn ($value) => $value !== '' && $value !== null
                ));
            }
        }

        return $conditions;
    }

    /**
     * @param array<int, array<string, mixed>> $actions
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeActions(array $actions): array
    {
        $normalized = [];

        foreach ($actions as $action) {
            if (! is_array($action)) {
                continue;
            }

            $action['continue_on_error'] = filter_var($action['continue_on_error'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $action['integration_id'] = $action['integration_id'] ?? null;

            if ($action['integration_id'] === '') {
                $action['integration_id'] = null;
            } elseif ($action['integration_id'] !== null) {
                $action['integration_id'] = (int) $action['integration_id'];
            }

            $action['config'] = Arr::wrap($action['config'] ?? []);
            if (isset($action['config']['headers_json']) && $action['config']['headers_json'] !== '') {
                $headers = json_decode($action['config']['headers_json'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw ValidationException::withMessages([
                        'actions' => __('socialprofile::messages.admin.automation.rules.actions.headers_error'),
                    ]);
                }
                $action['config']['headers'] = $headers;
            }
            unset($action['config']['headers_json']);
            $normalized[] = $action;
        }

        return array_values($normalized);
    }
}
