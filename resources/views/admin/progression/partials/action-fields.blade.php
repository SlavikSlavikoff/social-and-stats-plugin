@php($currentAction = $action->action ?? 'azuriom_role_grant')
@php($config = $action->config ?? [])
@php($integrationOptions = ($integrations ?? collect())->pluck('name', 'id'))
@php($isPunishmentThreshold = isset($threshold) && $threshold->is_punishment)
<div class="row g-3" data-action-container>
    <div class="col-md-6">
        <label class="form-label">{{ __('socialprofile::messages.progression.actions.type') }}</label>
        <select name="action" class="form-select" data-action-selector>
            @foreach([
                'azuriom_role_grant',
                'azuriom_role_revoke',
                'azuriom_permission_grant',
                'azuriom_permission_revoke',
                'plugin_feature_enable',
                'plugin_feature_disable',
                'external_webhook',
                'automation_rcon',
                'automation_bot',
            ] as $type)
                <option value="{{ $type }}" @selected($currentAction === $type)>
                    {{ __('socialprofile::messages.progression.actions.types.'.$type) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check">
            @if($isPunishmentThreshold)
                <input type="hidden" name="auto_revert" value="1">
            @endif
            <input class="form-check-input" type="checkbox" name="auto_revert" value="1" id="action-auto-{{ $action->id ?? 'new' }}" @checked($isPunishmentThreshold ? true : ($action?->auto_revert ?? true)) @disabled($isPunishmentThreshold)>
            <label class="form-check-label" for="action-auto-{{ $action->id ?? 'new' }}">{{ __('socialprofile::messages.progression.actions.auto_revert_toggle') }}</label>
            @if($isPunishmentThreshold)
                <small class="text-muted d-block">{{ __('socialprofile::messages.progression.actions.auto_revert_forced') }}</small>
            @endif
        </div>
    </div>

    <div class="col-12 action-config d-none" data-action-fields="azuriom_role_grant">
        <label class="form-label">{{ __('socialprofile::messages.progression.actions.role_id') }}</label>
        <input type="number" name="config[role_id]" class="form-control" value="{{ $config['role_id'] ?? '' }}" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.role_id') }}">
        <small class="text-muted">{{ __('socialprofile::messages.progression.actions.role_hint') }}</small>
    </div>

    <div class="col-12 action-config d-none" data-action-fields="azuriom_role_revoke">
        <div class="row">
            <div class="col">
                <label class="form-label">{{ __('socialprofile::messages.progression.actions.role_id') }}</label>
                <input type="number" name="config[role_id]" class="form-control" value="{{ $config['role_id'] ?? '' }}" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.role_id') }}">
            </div>
            <div class="col">
                <label class="form-label">{{ __('socialprofile::messages.progression.actions.fallback_role_id') }}</label>
                <input type="number" name="config[fallback_role_id]" class="form-control" value="{{ $config['fallback_role_id'] ?? '' }}" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.fallback_role_id') }}">
            </div>
        </div>
    </div>

    <div class="col-12 action-config d-none" data-action-fields="azuriom_permission_grant" data-action-fields-alt="azuriom_permission_revoke">
        <label class="form-label">{{ __('socialprofile::messages.progression.actions.permission') }}</label>
        <input type="text" name="config[permission]" class="form-control" value="{{ $config['permission'] ?? '' }}" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.permission') }}">
    </div>

    <div class="col-12 action-config d-none" data-action-fields="plugin_feature_enable" data-action-fields-alt="plugin_feature_disable">
        <label class="form-label">{{ __('socialprofile::messages.progression.actions.feature') }}</label>
        <input type="text" name="config[feature]" class="form-control" value="{{ $config['feature'] ?? '' }}" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.feature') }}">
    </div>

    <div class="col-12 action-config d-none" data-action-fields="external_webhook">
        <label class="form-label">{{ __('socialprofile::messages.progression.actions.webhook_url') }}</label>
        <input type="url" name="config[url]" class="form-control" value="{{ $config['url'] ?? '' }}" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.url') }}">
        <div class="row mt-2">
            <div class="col">
                <label class="form-label">{{ __('socialprofile::messages.progression.actions.method') }}</label>
                <input type="text" name="config[method]" class="form-control" value="{{ $config['method'] ?? 'POST' }}" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.method') }}">
            </div>
            <div class="col">
                <label class="form-label">{{ __('socialprofile::messages.progression.actions.timeout') }}</label>
                <input type="number" name="config[timeout]" class="form-control" value="{{ $config['timeout'] ?? 10 }}">
            </div>
        </div>
        <div class="mt-2">
            <label class="form-label">{{ __('socialprofile::messages.progression.actions.revert_url') }}</label>
            <input type="url" name="config[revert_url]" class="form-control" value="{{ $config['revert_url'] ?? '' }}" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.revert_url') }}">
        </div>
        <div class="mt-2">
            <label class="form-label">{{ __('socialprofile::messages.progression.actions.revert_method') }}</label>
            <input type="text" name="config[revert_method]" class="form-control" value="{{ $config['revert_method'] ?? ($config['method'] ?? 'POST') }}" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.method') }}">
        </div>
        <div class="mt-2">
            <label class="form-label">{{ __('socialprofile::messages.progression.actions.headers') }}</label>
            <textarea name="config[headers]" class="form-control" rows="2" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.headers') }}">@if(isset($config['headers']) && is_array($config['headers'])){{ collect($config['headers'])->map(fn($value, $key) => $key.': '.$value)->implode("\n") }}@endif</textarea>
            <small class="text-muted">{{ __('socialprofile::messages.progression.actions.headers_hint') }}</small>
        </div>
    </div>

    <div class="col-12 action-config d-none" data-action-fields="automation_rcon">
        <label class="form-label">{{ __('socialprofile::messages.progression.actions.integration') }}</label>
        <select name="config[integration_id]" class="form-select">
            <option value="">{{ __('socialprofile::messages.progression.actions.integration_placeholder') }}</option>
            @foreach($integrationOptions as $id => $name)
                <option value="{{ $id }}" @selected(($config['integration_id'] ?? null) == $id)>{{ $name }}</option>
            @endforeach
        </select>
        <div class="mt-2">
            <label class="form-label">{{ __('socialprofile::messages.progression.actions.command') }}</label>
            <input type="text" name="config[command]" class="form-control" value="{{ $config['command'] ?? '' }}" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.command') }}">
        </div>
        <div class="mt-2">
            <label class="form-label">{{ __('socialprofile::messages.progression.actions.revert_command') }}</label>
            <input type="text" name="config[revert_command]" class="form-control" value="{{ $config['revert_command'] ?? '' }}" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.revert_command') }}">
        </div>
    </div>

    <div class="col-12 action-config d-none" data-action-fields="automation_bot">
        <label class="form-label">{{ __('socialprofile::messages.progression.actions.integration') }}</label>
        <select name="config[integration_id]" class="form-select">
            <option value="">{{ __('socialprofile::messages.progression.actions.integration_placeholder') }}</option>
            @foreach($integrationOptions as $id => $name)
                <option value="{{ $id }}" @selected(($config['integration_id'] ?? null) == $id)>{{ $name }}</option>
            @endforeach
        </select>
        <div class="row mt-2">
            <div class="col">
                <label class="form-label">{{ __('socialprofile::messages.progression.actions.endpoint') }}</label>
                <input type="text" name="config[endpoint]" class="form-control" value="{{ $config['endpoint'] ?? '' }}" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.endpoint') }}">
            </div>
            <div class="col">
                <label class="form-label">{{ __('socialprofile::messages.progression.actions.method') }}</label>
                <input type="text" name="config[method]" class="form-control" value="{{ $config['method'] ?? 'POST' }}" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.method') }}">
            </div>
            <div class="col">
                <label class="form-label">{{ __('socialprofile::messages.progression.actions.revert_method') }}</label>
                <input type="text" name="config[revert_method]" class="form-control" value="{{ $config['revert_method'] ?? ($config['method'] ?? 'POST') }}" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.method') }}">
            </div>
        </div>
        <div class="mt-2">
            <label class="form-label">{{ __('socialprofile::messages.progression.actions.payload') }}</label>
            <textarea name="config[payload]" class="form-control" rows="3" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.payload') }}">@if(isset($config['payload'])){{ is_array($config['payload']) ? json_encode($config['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $config['payload'] }}@endif</textarea>
            <small class="text-muted">{{ __('socialprofile::messages.progression.actions.payload_hint') }}</small>
        </div>
        <div class="mt-2">
            <label class="form-label">{{ __('socialprofile::messages.progression.actions.revert_payload') }}</label>
            <textarea name="config[revert_payload]" class="form-control" rows="2" placeholder="{{ __('socialprofile::messages.progression.actions.placeholders.payload') }}">@if(isset($config['revert_payload'])){{ is_array($config['revert_payload']) ? json_encode($config['revert_payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $config['revert_payload'] }}@endif</textarea>
        </div>
    </div>
</div>
