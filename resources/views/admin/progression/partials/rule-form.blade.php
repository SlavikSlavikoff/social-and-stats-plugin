@php
    $conditionFields = old('conditions.field');
    $conditionOperators = old('conditions.operator');
    $conditionValues = old('conditions.value');

    if ($conditionFields !== null) {
        $conditionData = [];
        foreach ($conditionFields as $index => $field) {
            $conditionData[] = [
                'field' => $field,
                'operator' => $conditionOperators[$index] ?? '==',
                'value' => $conditionValues[$index] ?? null,
            ];
        }
    } else {
        $conditionData = $rule->conditions ?? [];
    }

    $rows = max(count($conditionData), 3);
@endphp
<div class="mb-3">
    <label class="form-label">{{ __('socialprofile::messages.progression.rules.name') }}</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $rule->name ?? '') }}" required>
    <small class="text-muted">{{ __('socialprofile::messages.progression.rules.name_hint') }}</small>
</div>
<div class="mb-3">
    <label class="form-label">{{ __('socialprofile::messages.progression.rules.trigger') }}</label>
    <select name="trigger_key" class="form-select">
        @foreach($triggers as $trigger)
            <option value="{{ $trigger }}" @selected(old('trigger_key', $rule->trigger_key ?? '') === $trigger)>{{ $trigger }}</option>
        @endforeach
    </select>
</div>
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">{{ __('socialprofile::messages.progression.rules.delta') }}</label>
        <input type="number" name="delta" class="form-control" value="{{ old('delta', $rule->delta ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('socialprofile::messages.progression.rules.delta_field') }}</label>
        <input type="text" name="delta_field" class="form-control" value="{{ old('delta_field', $rule->options['delta_field'] ?? '') }}">
        <small class="text-muted">{{ __('socialprofile::messages.progression.rules.delta_field_hint') }}</small>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('socialprofile::messages.progression.rules.delta_multiplier') }}</label>
        <input type="number" step="0.1" name="delta_multiplier" class="form-control" value="{{ old('delta_multiplier', $rule->options['delta_multiplier'] ?? 1) }}">
    </div>
</div>
<div class="row g-3 mt-2">
    <div class="col-md-4">
        <label class="form-label">{{ __('socialprofile::messages.progression.rules.source_type') }}</label>
        <select name="source_type" class="form-select">
            @foreach([
                \Azuriom\Plugin\InspiratoStats\Models\ProgressionRule::SOURCE_INTERNAL,
                \Azuriom\Plugin\InspiratoStats\Models\ProgressionRule::SOURCE_EXTERNAL,
                \Azuriom\Plugin\InspiratoStats\Models\ProgressionRule::SOURCE_MANUAL,
                \Azuriom\Plugin\InspiratoStats\Models\ProgressionRule::SOURCE_SCHEDULED,
            ] as $sourceType)
                <option value="{{ $sourceType }}" @selected(old('source_type', $rule->source_type ?? \Azuriom\Plugin\InspiratoStats\Models\ProgressionRule::SOURCE_INTERNAL) === $sourceType)>
                    {{ __('socialprofile::messages.progression.rules.sources.'.$sourceType) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('socialprofile::messages.progression.rules.cooldown') }}</label>
        <input type="number" name="cooldown_seconds" class="form-control" value="{{ old('cooldown_seconds', $rule->cooldown_seconds ?? '') }}">
        <small class="text-muted">{{ __('socialprofile::messages.progression.rules.cooldown_hint') }}</small>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="is_active" value="1" id="rule-active-{{ $rule->id ?? 'new' }}" @checked(old('is_active', $rule->is_active ?? true))>
            <label class="form-check-label" for="rule-active-{{ $rule->id ?? 'new' }}">{{ __('socialprofile::messages.progression.rules.active') }}</label>
        </div>
    </div>
</div>
<div class="mt-3">
    <label class="form-label">{{ __('socialprofile::messages.progression.rules.conditions') }}</label>
    @for($i = 0; $i < $rows; $i++)
        @php($condition = $conditionData[$i] ?? ['field' => '', 'operator' => '==', 'value' => ''])
        <div class="row g-2 mb-2">
            <div class="col-md-5">
                <input type="text" name="conditions[field][]" class="form-control" placeholder="payload.delta" value="{{ $condition['field'] }}">
            </div>
            <div class="col-md-3">
                <select name="conditions[operator][]" class="form-select">
                    @foreach(['==','!=','>','>=','<','<=','in','not_in','contains','not_contains'] as $operator)
                        <option value="{{ $operator }}" @selected($condition['operator'] === $operator)>{{ $operator }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="conditions[value][]" class="form-control" value="{{ $condition['value'] }}">
            </div>
        </div>
    @endfor
    <small class="text-muted">{{ __('socialprofile::messages.progression.rules.conditions_hint') }}</small>
</div>
