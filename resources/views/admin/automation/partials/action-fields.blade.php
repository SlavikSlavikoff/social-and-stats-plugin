@php
    $type = data_get($action, 'type', array_key_first($actionTypes));
    $config = data_get($action, 'config', []);
    $integrationId = data_get($action, 'integration_id');
    $continueOnError = (bool) data_get($action, 'continue_on_error', false);
@endphp
<div class="automation-action border rounded p-3 mb-3" data-action-block>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <strong>{{ __('socialprofile::messages.admin.automation.rules.actions.block_title') }}</strong>
        <button type="button" class="btn btn-sm btn-outline-danger automation-action-remove" data-action-remove>
            {{ __('socialprofile::messages.admin.automation.rules.actions.remove') }}
        </button>
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.actions.type') }}</label>
            <select name="actions[{{ $index }}][type]" class="form-select automation-action-type" data-action-type>
                @foreach($actionTypes as $actionKey => $info)
                    <option value="{{ $actionKey }}" @selected($actionKey === $type)>{{ $info['label'] ?? $actionKey }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.actions.integration') }}</label>
            <select name="actions[{{ $index }}][integration_id]" class="form-select">
                <option value="">{{ __('socialprofile::messages.admin.automation.rules.actions.integration_placeholder') }}</option>
                @foreach($integrations as $integration)
                    <option value="{{ $integration->id }}" @selected($integration->id === $integrationId)>
                        {{ $integration->name }} ({{ $integration->type }})
                    </option>
                @endforeach
            </select>
            <small class="text-muted">{{ __('socialprofile::messages.admin.automation.rules.actions.integration_help') }}</small>
        </div>
        <div class="col-md-4 d-flex align-items-center">
            <div class="form-check mt-4">
                <input type="checkbox" class="form-check-input" name="actions[{{ $index }}][continue_on_error]" value="1" @checked($continueOnError)>
                <label class="form-check-label">
                    {{ __('socialprofile::messages.admin.automation.rules.actions.continue_on_error') }}
                </label>
            </div>
        </div>
    </div>
    <div class="mt-3 action-fields" data-action-type-fields="minecraft_rcon_command" style="{{ $type === 'minecraft_rcon_command' ? '' : 'display:none;' }}">
        <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.actions.command') }}</label>
        <textarea class="form-control" name="actions[{{ $index }}][config][command]" rows="2">{{ $config['command'] ?? '' }}</textarea>
        <small class="text-muted d-block">{{ __('socialprofile::messages.admin.automation.rules.actions.command_help') }}</small>
    </div>
    <div class="mt-3 action-fields" data-action-type-fields="minecraft_db_query" style="{{ $type === 'minecraft_db_query' ? '' : 'display:none;' }}">
        <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.actions.query') }}</label>
        <textarea class="form-control" name="actions[{{ $index }}][config][query]" rows="3">{{ $config['query'] ?? '' }}</textarea>
        <small class="text-muted d-block">{{ __('socialprofile::messages.admin.automation.rules.actions.query_help') }}</small>
    </div>
    <div class="mt-3 action-fields" data-action-type-fields="social_bot_request" style="{{ $type === 'social_bot_request' ? '' : 'display:none;' }}">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.actions.http_method') }}</label>
                <input type="text" class="form-control" name="actions[{{ $index }}][config][method]" value="{{ strtoupper($config['method'] ?? 'POST') }}">
            </div>
            <div class="col-md-9">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.actions.url') }}</label>
                <input type="text" class="form-control" name="actions[{{ $index }}][config][url]" value="{{ $config['url'] ?? '' }}">
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.actions.headers') }}</label>
                <textarea class="form-control" name="actions[{{ $index }}][config][headers_json]" rows="2">{{ isset($config['headers']) ? json_encode($config['headers']) : '' }}</textarea>
                <small class="text-muted">{{ __('socialprofile::messages.admin.automation.rules.actions.headers_help') }}</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.actions.body') }}</label>
                <textarea class="form-control" name="actions[{{ $index }}][config][body]" rows="3">{{ $config['body'] ?? '' }}</textarea>
                <small class="text-muted">{{ __('socialprofile::messages.admin.automation.rules.actions.body_help') }}</small>
            </div>
        </div>
    </div>
    <div class="mt-3 action-fields" data-action-type-fields="internal_reward" style="{{ $type === 'internal_reward' ? '' : 'display:none;' }}">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">{{ __('socialprofile::messages.metrics.social_score') }}</label>
                <input type="number" step="1" class="form-control" name="actions[{{ $index }}][config][social_score]" value="{{ $config['social_score'] ?? 0 }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('socialprofile::messages.metrics.coins') }}</label>
                <input type="number" step="0.01" class="form-control" name="actions[{{ $index }}][config][coins]" value="{{ $config['coins'] ?? 0 }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('socialprofile::messages.metrics.activity') }}</label>
                <input type="number" step="1" class="form-control" name="actions[{{ $index }}][config][activity]" value="{{ $config['activity'] ?? 0 }}">
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.actions.direction') }}</label>
                <select class="form-select" name="actions[{{ $index }}][config][direction]">
                    <option value="increase" @selected(($config['direction'] ?? 'increase') === 'increase')>{{ __('socialprofile::messages.admin.automation.rules.actions.direction_plus') }}</option>
                    <option value="decrease" @selected(($config['direction'] ?? 'increase') === 'decrease')>{{ __('socialprofile::messages.admin.automation.rules.actions.direction_minus') }}</option>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.actions.note') }}</label>
                <input type="text" class="form-control" name="actions[{{ $index }}][config][note]" value="{{ $config['note'] ?? '' }}">
            </div>
        </div>
        <small class="text-muted d-block mt-2">{{ __('socialprofile::messages.admin.automation.rules.actions.reward_help') }}</small>
    </div>
    <div class="mt-3 action-fields" data-action-type-fields="assign_role" style="{{ $type === 'assign_role' ? '' : 'display:none;' }}">
        <label class="form-label">{{ __('socialprofile::messages.admin.automation.rules.actions.assign_role') }}</label>
        <select class="form-select" name="actions[{{ $index }}][config][role_id]">
            <option value="">{{ __('socialprofile::messages.admin.automation.rules.actions.assign_role_help') }}</option>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected((int) ($config['role_id'] ?? 0) === $role->id)>{{ $role->name }}</option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-2">{{ __('socialprofile::messages.admin.automation.rules.actions.assign_role_help') }}</small>
    </div>
</div>
