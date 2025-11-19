@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.court.settings.title'))

@section('content')
@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('socialprofile::messages.court.settings.general') }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('socialprofile.admin.court.settings.general') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.court.settings.ban_role') }}</label>
                        <select name="ban_role_id" class="form-select">
                            <option value="">{{ __('socialprofile::messages.court.settings.none') }}</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected($settings['ban_role_id'] === $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.court.settings.mute_role') }}</label>
                        <select name="mute_role_id" class="form-select">
                            <option value="">{{ __('socialprofile::messages.court.settings.none') }}</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected($settings['mute_role_id'] === $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.court.settings.novice_role') }}</label>
                        <select name="novice_role_id" class="form-select">
                            <option value="">{{ __('socialprofile::messages.court.settings.none') }}</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected($settings['novice_role_id'] === $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.court.settings.visibility') }}</label>
                        <select name="default_visibility" class="form-select">
                            @foreach(['private','judges','public'] as $visibility)
                                <option value="{{ $visibility }}" @selected($settings['default_visibility'] === $visibility)>{{ __('socialprofile::messages.court.visibility.'.$visibility) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('socialprofile::messages.court.settings.per_judge_hour_limit') }}</label>
                            <input type="number" name="per_judge_hour_limit" class="form-control" min="1" value="{{ $settings['per_judge_hour_limit'] }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('socialprofile::messages.court.settings.per_user_daily_limit') }}</label>
                            <input type="number" name="per_user_daily_limit" class="form-control" min="1" value="{{ $settings['per_user_daily_limit'] }}">
                        </div>
                    </div>
                    <button class="btn btn-primary">{{ __('socialprofile::messages.actions.save') }}</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('socialprofile::messages.court.settings.webhooks') }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('socialprofile.admin.court.webhooks.store') }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label">{{ __('socialprofile::messages.court.settings.webhook_name') }}</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('socialprofile::messages.court.settings.webhook_url') }}</label>
                        <input type="url" class="form-control" name="url" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('socialprofile::messages.court.settings.webhook_secret') }}</label>
                        <input type="text" class="form-control" name="secret">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.court.settings.webhook_events') }}</label>
                        <select class="form-select" name="events[]" multiple>
                            <option value="issued">issued</option>
                            <option value="reverted">reverted</option>
                            <option value="updated">updated</option>
                        </select>
                    </div>
                    <button class="btn btn-outline-primary">{{ __('socialprofile::messages.actions.add') }}</button>
                </form>
                <hr>
                <ul class="list-group">
                    @forelse($webhooks as $webhook)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $webhook->name }}</strong>
                                <small class="d-block text-muted">{{ $webhook->url }}</small>
                                <small class="text-muted">{{ implode(', ', $webhook->events ?? []) }}</small>
                            </div>
                            <form method="POST" action="{{ route('socialprofile.admin.court.webhooks.destroy', $webhook) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">{{ __('socialprofile::messages.actions.delete') }}</button>
                            </form>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">{{ __('socialprofile::messages.court.settings.webhook_empty') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="card-title mb-1">{{ __('socialprofile::messages.court.settings.templates') }}</h5>
                <p class="mb-0 text-muted">{{ __('socialprofile::messages.court.settings.templates_description') }}</p>
            </div>
            <a href="{{ route('socialprofile.admin.court.templates.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-layer-group me-1"></i>{{ __('socialprofile::messages.court.templates.title') }}
            </a>
        </div>
    </div>
</div>
@endsection
