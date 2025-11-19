<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionRating;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionRule;
use Azuriom\Plugin\InspiratoStats\Support\Progression\ProgressionRuleEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgressionRulesController extends Controller
{
    public function index(ProgressionRating $rating)
    {
        $rules = $rating->rules()->orderBy('name')->get();

        return view('socialprofile::admin.progression.rules', [
            'rating' => $rating,
            'rules' => $rules,
            'triggers' => ProgressionRuleEngine::AVAILABLE_TRIGGERS,
        ]);
    }

    public function store(Request $request, ProgressionRating $rating): RedirectResponse
    {
        $data = $this->validateRule($request);
        $rating->rules()->create($data);

        return back()->with('status', __('socialprofile::messages.progression.rules.created'));
    }

    public function update(Request $request, ProgressionRule $rule): RedirectResponse
    {
        $data = $this->validateRule($request);
        $rule->update($data);

        return back()->with('status', __('socialprofile::messages.progression.rules.updated'));
    }

    public function destroy(ProgressionRule $rule): RedirectResponse
    {
        $rule->delete();

        return back()->with('status', __('socialprofile::messages.progression.rules.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateRule(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger_key' => ['required', Rule::in(ProgressionRuleEngine::AVAILABLE_TRIGGERS)],
            'source_type' => ['required', Rule::in([
                ProgressionRule::SOURCE_INTERNAL,
                ProgressionRule::SOURCE_EXTERNAL,
                ProgressionRule::SOURCE_MANUAL,
                ProgressionRule::SOURCE_SCHEDULED,
            ])],
            'delta' => ['required', 'integer'],
            'cooldown_seconds' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'delta_field' => ['nullable', 'string', 'max:255'],
            'delta_multiplier' => ['nullable', 'numeric'],
        ]);

        return [
            'name' => $data['name'],
            'trigger_key' => $data['trigger_key'],
            'source_type' => $data['source_type'],
            'delta' => (int) $data['delta'],
            'cooldown_seconds' => $data['cooldown_seconds'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'conditions' => $this->collectConditions($request),
            'options' => $this->collectOptions($data),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function collectConditions(Request $request): array
    {
        $fields = $request->input('conditions.field', []);
        $operators = $request->input('conditions.operator', []);
        $values = $request->input('conditions.value', []);
        $conditions = [];

        foreach ($fields as $index => $field) {
            $field = trim((string) $field);
            $operator = $operators[$index] ?? '==';
            $value = $values[$index] ?? null;

            if ($field === '') {
                continue;
            }

            $conditions[] = [
                'field' => $field,
                'operator' => $operator,
                'value' => $value,
            ];
        }

        return $conditions;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function collectOptions(array $data): array
    {
        $options = [];

        if (! empty($data['delta_field'])) {
            $options['delta_field'] = $data['delta_field'];
        }

        if (isset($data['delta_multiplier']) && $data['delta_multiplier'] !== null) {
            $options['delta_multiplier'] = (float) $data['delta_multiplier'];
        }

        return $options;
    }
}
