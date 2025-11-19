@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.court.templates.title'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">{{ __('socialprofile::messages.court.templates.title') }}</h1>
    <form method="POST" action="{{ route('socialprofile.admin.court.templates.refresh') }}">
        @csrf
        <button class="btn btn-outline-secondary" onclick="return confirm('{{ __('socialprofile::messages.court.templates.refresh_confirm') }}');">
            <i class="fas fa-sync-alt"></i> {{ __('socialprofile::messages.court.templates.refresh_action') }}
        </button>
    </form>
</div>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="row">
    <div class="col-lg-7">
        @forelse($templates as $template)
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title mb-0">{{ $template->name }}</h5>
                            <span class="badge bg-light text-dark">{{ $template->key }}</span>
                            @if(! $template->is_active)
                                <span class="badge bg-warning text-dark">{{ __('socialprofile::messages.court.templates.disabled') }}</span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('socialprofile.admin.court.templates.manage.destroy', $template) }}" onsubmit="return confirm('{{ __('socialprofile::messages.court.templates.archive_confirm') }}');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-archive"></i>
                            </button>
                        </form>
                    </div>
                    <p class="text-muted small mb-2">{{ $template->base_comment }}</p>
                    <pre class="bg-light border rounded p-2 small mb-3"><code>{{ json_encode($template->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                    <form method="POST" action="{{ route('socialprofile.admin.court.templates.manage.update', $template) }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('socialprofile::messages.court.templates.fields.name') }}</label>
                                <input type="text" name="name" class="form-control" value="{{ $template->name }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('socialprofile::messages.court.templates.fields.key') }}</label>
                                <input type="text" name="key" class="form-control" value="{{ $template->key }}" required>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="template_active_{{ $template->id }}" @checked($template->is_active)>
                                    <label class="form-check-label" for="template_active_{{ $template->id }}">
                                        {{ __('socialprofile::messages.court.templates.fields.active') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('socialprofile::messages.court.templates.fields.comment') }}</label>
                                <textarea name="base_comment" class="form-control" rows="2">{{ $template->base_comment }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('socialprofile::messages.court.templates.fields.payload') }}</label>
                                <textarea name="payload" class="form-control" rows="6">{{ json_encode($template->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                                <small class="text-muted">{{ __('socialprofile::messages.court.templates.payload_hint') }}</small>
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button class="btn btn-primary">{{ __('socialprofile::messages.actions.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="alert alert-info">{{ __('socialprofile::messages.court.templates.empty') }}</div>
        @endforelse
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('socialprofile::messages.court.templates.create_title') }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('socialprofile.admin.court.templates.manage.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.court.templates.fields.name') }}</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.court.templates.fields.key') }}</label>
                        <input type="text" name="key" class="form-control" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="template_create_active" checked>
                        <label class="form-check-label" for="template_create_active">{{ __('socialprofile::messages.court.templates.fields.active') }}</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.court.templates.fields.comment') }}</label>
                        <textarea name="base_comment" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.court.templates.fields.payload') }}</label>
                        <textarea name="payload" class="form-control" rows="6" placeholder='{"punishment":{"socialrating":-10}}'></textarea>
                        <small class="text-muted">{{ __('socialprofile::messages.court.templates.payload_hint') }}</small>
                    </div>
                    <div class="text-end">
                        <button class="btn btn-primary">{{ __('socialprofile::messages.actions.create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
