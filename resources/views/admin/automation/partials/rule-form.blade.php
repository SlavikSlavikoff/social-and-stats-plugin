@php
    $formId = $formId ?? 'automation-rule';
    $hasOld = old('form_id') === $formId;
    $triggerValue = $hasOld ? old('trigger_type') : ($rule->trigger_type ?? array_key_first($triggers));
    $conditions = $hasOld ? (array) old('conditions', []) : ($rule->conditions ?? []);
    $actionsList = $hasOld ? old('actions', []) : ($rule->actions ?? []);
    $actionsList = array_values($actionsList);
    if ($actionsList === [] || $actionsList === null) {
        $actionsList = [[]];
    }
    $fromRoles = $conditions['from_roles'] ?? ['*'];
    $toRoles = $conditions['to_roles'] ?? ['*'];
    $fromLevels = $conditions['from_levels'] ?? ['*'];
    $toLevels = $conditions['to_levels'] ?? ['*'];
    $violationTypesList = is_array($violationTypes ?? null) ? $violationTypes : [];
    $selectedViolationTypes = $conditions['violation_types'] ?? ['*'];
    $courtActionsList = is_array($courtActions ?? null) ? $courtActions : [];
    $courtStatusesList = is_array($courtStatuses ?? null) ? $courtStatuses : [];
    $courtModesList = is_array($courtModes ?? null) ? $courtModes : [];
    $selectedCourtActions = $conditions['case_actions'] ?? ['*'];
    $selectedCourtStatuses = $conditions['case_statuses'] ?? ['*'];
    $selectedCourtModes = $conditions['case_modes'] ?? ['*'];
@endphp
<input type="hidden" name="form_id" value="{{ $formId }}">
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.fields.name') }}</label>
        <input type="text" class="form-control" name="name" value="{{ $hasOld ? old('name') : ($rule->name ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.fields.trigger') }}</label>
        <select class="form-select automation-rule-trigger" name="trigger_type" data-trigger-select>
            @foreach($triggers as $key => $info)
                <option value="{{ $key }}" @selected($key === $triggerValue)>{{ $info['label'] ?? $key }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.fields.priority') }}</label>
        <input type="number" class="form-control" name="priority" value="{{ $hasOld ? old('priority', 0) : ($rule->priority ?? 0) }}">
    </div>
</div>
<div class="row g-3 mt-2">
    <div class="col-md-4 d-flex align-items-center">
        @php
            $enabled = $hasOld ? (bool) old('enabled') : ($rule->enabled ?? true);
        @endphp
        <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" name="enabled" value="1" @checked($enabled)>
            <label class="form-check-label">{{ __('socialprofile::messages.admin.automation.rules.fields.enabled') }}</label>
        </div>
    </div>
    <div class="col-md-8">
        <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.fields.description') }}</label>
        <textarea class="form-control" name="description" rows="2">{{ $hasOld ? old('description') : ($rule->description ?? '') }}</textarea>
    </div>
</div>
<div class="mt-4 automation-conditions" data-conditions="{{ $formId }}">
    <h5>{{ __('socialprofile::messages.admin.automation.rules.conditions.title') }}</h5>
    <div class="condition-panel" data-condition="role_changed" style="{{ $triggerValue === 'role_changed' ? '' : 'display:none;' }}">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.role_from') }}</label>
                <select class="form-select" name="conditions[from_roles][]" multiple size="6">
                    <option value="*" @selected(in_array('*', $fromRoles, true))>{{ __('socialprofile::messages.admin.automation.rules.conditions.any_role') }}</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected(in_array($role->id, $fromRoles))>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.role_to') }}</label>
                <select class="form-select" name="conditions[to_roles][]" multiple size="6">
                    <option value="*" @selected(in_array('*', $toRoles, true))>{{ __('socialprofile::messages.admin.automation.rules.conditions.any_role') }}</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected(in_array($role->id, $toRoles))>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <small class="text-muted">{{ __('socialprofile::messages.admin.automation.rules.conditions.role_help') }}</small>
    </div>
    <div class="condition-panel" data-condition="trust_level_changed" style="{{ $triggerValue === 'trust_level_changed' ? '' : 'display:none;' }}">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.trust_from') }}</label>
                <select class="form-select" name="conditions[from_levels][]" multiple size="5">
                    <option value="*" @selected(in_array('*', $fromLevels, true))>{{ __('socialprofile::messages.admin.automation.rules.conditions.any_trust') }}</option>
                    @foreach($trustLevels as $level)
                        <option value="{{ $level }}" @selected(in_array($level, $fromLevels, true))>{{ __('socialprofile::messages.trust.levels.'.$level) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.trust_to') }}</label>
                <select class="form-select" name="conditions[to_levels][]" multiple size="5">
                    <option value="*" @selected(in_array('*', $toLevels, true))>{{ __('socialprofile::messages.admin.automation.rules.conditions.any_trust') }}</option>
                    @foreach($trustLevels as $level)
                        <option value="{{ $level }}" @selected(in_array($level, $toLevels, true))>{{ __('socialprofile::messages.trust.levels.'.$level) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-3">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.from_rank_min') }}</label>
                <input type="number" class="form-control" name="conditions[from_rank_min]" value="{{ $conditions['from_rank_min'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.from_rank_max') }}</label>
                <input type="number" class="form-control" name="conditions[from_rank_max]" value="{{ $conditions['from_rank_max'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.to_rank_min') }}</label>
                <input type="number" class="form-control" name="conditions[to_rank_min]" value="{{ $conditions['to_rank_min'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.to_rank_max') }}</label>
                <input type="number" class="form-control" name="conditions[to_rank_max]" value="{{ $conditions['to_rank_max'] ?? '' }}">
            </div>
        </div>
        <small class="text-muted">{{ __('socialprofile::messages.admin.automation.rules.conditions.trust_help') }}</small>
    </div>
    <div class="condition-panel" data-condition="activity_changed" style="{{ $triggerValue === 'activity_changed' ? '' : 'display:none;' }}">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.activity_points_min') }}</label>
                <input type="number" class="form-control" name="conditions[points_min]" value="{{ $conditions['points_min'] ?? '' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.activity_points_max') }}</label>
                <input type="number" class="form-control" name="conditions[points_max]" value="{{ $conditions['points_max'] ?? '' }}">
            </div>
        </div>
    </div>
    <div class="condition-panel" data-condition="coins_changed" style="{{ $triggerValue === 'coins_changed' ? '' : 'display:none;' }}">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.coins_min') }}</label>
                <input type="number" step="0.01" class="form-control" name="conditions[balance_min]" value="{{ $conditions['balance_min'] ?? '' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.coins_max') }}</label>
                <input type="number" step="0.01" class="form-control" name="conditions[balance_max]" value="{{ $conditions['balance_max'] ?? '' }}">
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.hold_min') }}</label>
                <input type="number" step="0.01" class="form-control" name="conditions[hold_min]" value="{{ $conditions['hold_min'] ?? '' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.hold_max') }}</label>
                <input type="number" step="0.01" class="form-control" name="conditions[hold_max]" value="{{ $conditions['hold_max'] ?? '' }}">
            </div>
        </div>
    </div>
    <div class="condition-panel" data-condition="social_stats_updated" style="{{ $triggerValue === 'social_stats_updated' ? '' : 'display:none;' }}">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.played_minutes_min') }}</label>
                <input type="number" class="form-control" name="conditions[played_minutes_min]" value="{{ $conditions['played_minutes_min'] ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.played_minutes_max') }}</label>
                <input type="number" class="form-control" name="conditions[played_minutes_max]" value="{{ $conditions['played_minutes_max'] ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.kills_min') }}</label>
                <input type="number" class="form-control" name="conditions[kills_min]" value="{{ $conditions['kills_min'] ?? '' }}">
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.kills_max') }}</label>
                <input type="number" class="form-control" name="conditions[kills_max]" value="{{ $conditions['kills_max'] ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.deaths_min') }}</label>
                <input type="number" class="form-control" name="conditions[deaths_min]" value="{{ $conditions['deaths_min'] ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.deaths_max') }}</label>
                <input type="number" class="form-control" name="conditions[deaths_max]" value="{{ $conditions['deaths_max'] ?? '' }}">
            </div>
        </div>
    </div>
    <div class="condition-panel" data-condition="violation_added" style="{{ $triggerValue === 'violation_added' ? '' : 'display:none;' }}">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.violation_types') }}</label>
                <select class="form-select" name="conditions[violation_types][]" multiple size="5">
                    <option value="*" @selected(in_array('*', $selectedViolationTypes, true))>{{ __('socialprofile::messages.admin.automation.rules.conditions.any_violation_type') }}</option>
                    @foreach($violationTypesList as $typeKey => $label)
                        <option value="{{ $typeKey }}" @selected(in_array($typeKey, $selectedViolationTypes, true))>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.violation_points_min') }}</label>
                <input type="number" class="form-control" name="conditions[violation_points_min]" value="{{ $conditions['violation_points_min'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.violation_points_max') }}</label>
                <input type="number" class="form-control" name="conditions[violation_points_max]" value="{{ $conditions['violation_points_max'] ?? '' }}">
            </div>
        </div>
    </div>
    <div class="condition-panel" data-condition="court_decision_changed" style="{{ $triggerValue === 'court_decision_changed' ? '' : 'display:none;' }}">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.court_actions') }}</label>
                <select class="form-select" name="conditions[case_actions][]" multiple size="5">
                    <option value="*" @selected(in_array('*', $selectedCourtActions, true))>{{ __('socialprofile::messages.admin.automation.rules.conditions.any_case_value') }}</option>
                    @foreach($courtActionsList as $actionKey => $label)
                        <option value="{{ $actionKey }}" @selected(in_array($actionKey, $selectedCourtActions, true))>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.court_statuses') }}</label>
                <select class="form-select" name="conditions[case_statuses][]" multiple size="5">
                    <option value="*" @selected(in_array('*', $selectedCourtStatuses, true))>{{ __('socialprofile::messages.admin.automation.rules.conditions.any_case_value') }}</option>
                    @foreach($courtStatusesList as $statusKey => $label)
                        <option value="{{ $statusKey }}" @selected(in_array($statusKey, $selectedCourtStatuses, true))>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.court_modes') }}</label>
                <select class="form-select" name="conditions[case_modes][]" multiple size="3">
                    <option value="*" @selected(in_array('*', $selectedCourtModes, true))>{{ __('socialprofile::messages.admin.automation.rules.conditions.any_case_value') }}</option>
                    @foreach($courtModesList as $modeKey => $label)
                        <option value="{{ $modeKey }}" @selected(in_array($modeKey, $selectedCourtModes, true))>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.court_executor') }}</label>
                <input type="text" class="form-control" name="conditions[case_executor]" value="{{ $conditions['case_executor'] ?? '' }}">
            </div>
        </div>
    </div>
    <div class="condition-panel" data-condition="monthly_top" style="{{ $triggerValue === 'monthly_top' ? '' : 'display:none;' }}">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.metric') }}</label>
                <select class="form-select" name="conditions[metrics][]" multiple size="4">
                    <option value="*" @selected(isset($conditions['metrics']) && in_array('*', $conditions['metrics'], true))>{{ __('socialprofile::messages.admin.automation.rules.conditions.any_metric') }}</option>
                    <option value="social_score" @selected(isset($conditions['metrics']) && in_array('social_score', $conditions['metrics'], true))>{{ __('socialprofile::messages.metrics.social_score') }}</option>
                    <option value="activity" @selected(isset($conditions['metrics']) && in_array('activity', $conditions['metrics'], true))>{{ __('socialprofile::messages.metrics.activity') }}</option>
                    <option value="coins" @selected(isset($conditions['metrics']) && in_array('coins', $conditions['metrics'], true))>{{ __('socialprofile::messages.metrics.coins') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.position_min') }}</label>
                <input type="number" class="form-control" name="conditions[position_min]" value="{{ $conditions['position_min'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.conditions.position_max') }}</label>
                <input type="number" class="form-control" name="conditions[position_max]" value="{{ $conditions['position_max'] ?? '' }}">
            </div>
        </div>
        <small class="text-muted">{{ __('socialprofile::messages.admin.automation.rules.conditions.monthly_help') }}</small>
    </div>
</div>
<div class="mt-4">
    <h5>{{ __('socialprofile::messages.admin.automation.rules.actions.title') }}</h5>
    <div class="automation-actions" data-form="{{ $formId }}" data-next-index="{{ count($actionsList) }}">
        <div class="automation-actions__items">
            @foreach($actionsList as $index => $actionData)
                @include('socialprofile::admin.automation.partials.action-fields', [
                    'index' => $index,
                    'action' => $actionData,
                    'integrations' => $integrations,
                    'actionTypes' => $actionTypes,
                    'roles' => $roles,
                ])
            @endforeach
        </div>
        <button class="btn btn-outline-secondary btn-sm automation-action-add mt-2" type="button" data-add-action="{{ $formId }}">
            {{ __('socialprofile::messages.admin.automation.rules.actions.add') }}
        </button>
    </div>
</div>
<div class="mt-4 text-end">
    <button class="btn btn-primary" type="submit">{{ __('socialprofile::messages.admin.automation.rules.save') }}</button>
</div>
