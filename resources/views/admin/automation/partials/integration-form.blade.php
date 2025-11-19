@php
    $formId = $formId ?? 'integration';
    $hasOld = old('form_id') === $formId;
    $config = $hasOld ? (array) old('config', []) : ($integration->config ?? []);
    $selectedType = $hasOld ? old('type') : ($integration->type ?? array_key_first($integrationTypes));
@endphp
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.fields.name') }}</label>
        <input type="text" class="form-control" name="name" value="{{ $hasOld ? old('name') : ($integration->name ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.fields.type') }}</label>
        <select class="form-select" name="type" required>
            @foreach($integrationTypes as $key => $label)
                <option value="{{ $key }}" @selected($key === $selectedType)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        @php
            $isDefault = $hasOld ? (bool) old('is_default') : ($integration->is_default ?? false);
        @endphp
        <div class="form-check mt-4">
            <input type="checkbox" class="form-check-input" name="is_default" value="1" @checked($isDefault)>
            <label class="form-check-label">{{ __('socialprofile::messages.admin.automation.integrations.fields.is_default') }}</label>
            <small class="text-muted d-block">{{ __('socialprofile::messages.admin.automation.integrations.fields.is_default_help') }}</small>
        </div>
    </div>
</div>
<div class="mt-3">
    <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.fields.description') }}</label>
    <textarea class="form-control" name="description" rows="2">{{ $hasOld ? old('description') : ($integration->description ?? '') }}</textarea>
    <small class="text-muted">{{ __('socialprofile::messages.admin.automation.integrations.fields.description_help') }}</small>
</div>

<div class="mt-4" data-integration-fields="minecraft_rcon" style="{{ $selectedType === 'minecraft_rcon' ? '' : 'display:none;' }}">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.rcon.host') }}</label>
            <input type="text" class="form-control" name="config[host]" value="{{ $config['host'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.rcon.port') }}</label>
            <input type="number" class="form-control" name="config[port]" value="{{ $config['port'] ?? 25575 }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.rcon.password') }}</label>
            <input type="password" class="form-control" name="config[password]" value="{{ $config['password'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.rcon.timeout') }}</label>
            <input type="number" class="form-control" name="config[timeout]" value="{{ $config['timeout'] ?? 5 }}">
        </div>
    </div>
</div>

<div class="mt-4" data-integration-fields="minecraft_db" style="{{ $selectedType === 'minecraft_db' ? '' : 'display:none;' }}">
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.db.driver') }}</label>
            <select class="form-select" name="config[driver]">
                <option value="mysql" @selected(($config['driver'] ?? 'mysql') === 'mysql')>MySQL</option>
                <option value="pgsql" @selected(($config['driver'] ?? 'mysql') === 'pgsql')>PostgreSQL</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.db.host') }}</label>
            <input type="text" class="form-control" name="config[host]" value="{{ $config['host'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.db.port') }}</label>
            <input type="number" class="form-control" name="config[port]" value="{{ $config['port'] ?? 3306 }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.db.database') }}</label>
            <input type="text" class="form-control" name="config[database]" value="{{ $config['database'] ?? '' }}">
        </div>
    </div>
    <div class="row g-3 mt-2">
        <div class="col-md-4">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.db.username') }}</label>
            <input type="text" class="form-control" name="config[username]" value="{{ $config['username'] ?? '' }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.db.password') }}</label>
            <input type="password" class="form-control" name="config[password]" value="{{ $config['password'] ?? '' }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.db.charset') }}</label>
            <input type="text" class="form-control" name="config[charset]" value="{{ $config['charset'] ?? 'utf8mb4' }}">
        </div>
    </div>
</div>

<div class="mt-4" data-integration-fields="social_bot" style="{{ $selectedType === 'social_bot' ? '' : 'display:none;' }}">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.bot.url') }}</label>
            <input type="text" class="form-control" name="config[base_url]" value="{{ $config['base_url'] ?? '' }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.bot.token') }}</label>
            <input type="text" class="form-control" name="config[token]" value="{{ $config['token'] ?? '' }}">
        </div>
    </div>
    <div class="row g-3 mt-2">
        <div class="col-md-12">
            <label class="form-label">{{ __('socialprofile::messages.admin.automation.integrations.bot.headers') }}</label>
            <textarea class="form-control" name="config[default_headers_json]" rows="2">{{ isset($config['default_headers']) ? json_encode($config['default_headers']) : '' }}</textarea>
            <small class="text-muted">{{ __('socialprofile::messages.admin.automation.integrations.bot.headers_help') }}</small>
        </div>
    </div>
</div>
