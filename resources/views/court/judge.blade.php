@extends('layouts.app')

@section('title', __('socialprofile::messages.court.judge.title'))

@push('styles')
<link rel="stylesheet" href="{{ plugin_asset('socialprofile', 'css/style.css') }}">
@endpush

@section('content')
<div class="container py-4">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-2">
                        <div>
                            <h1 class="h4 mb-1">{{ __('socialprofile::messages.court.judge.heading') }}</h1>
                            <p class="mb-0 text-muted">{{ __('socialprofile::messages.court.judge.description') }}</p>
                        </div>
                        <span class="badge bg-primary text-uppercase">{{ __('socialprofile::messages.court.judge.permission_badge') }}</span>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('socialprofile::messages.court.auto.title') }}</h5>
                            <small class="text-muted">{{ __('socialprofile::messages.court.auto.subtitle') }}</small>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('socialprofile.court.decisions.auto.store') }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label" for="auto-subject">{{ __('socialprofile::messages.court.fields.subject') }}</label>
                                    <input type="text" id="auto-subject" name="subject" class="form-control @error('subject') is-invalid @enderror" placeholder="{{ __('socialprofile::messages.court.placeholders.subject') }}" value="{{ old('subject') }}" required>
                                    @error('subject')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="auto-template">{{ __('socialprofile::messages.court.fields.template') }}</label>
                                    <select id="auto-template" name="template_key" class="form-select @error('template_key') is-invalid @enderror" required>
                                        <option value="">{{ __('socialprofile::messages.court.fields.template_placeholder') }}</option>
                                        @foreach($templates as $template)
                                            <option value="{{ $template->key }}" @selected(old('template_key') === $template->key)>{{ $template->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('template_key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="auto-comment">{{ __('socialprofile::messages.court.fields.comment') }}</label>
                                    <textarea id="auto-comment" name="comment" maxlength="{{ $limits['comment_max'] }}" class="form-control @error('comment') is-invalid @enderror" rows="3" placeholder="{{ __('socialprofile::messages.court.placeholders.comment') }}">{{ old('comment') }}</textarea>
                                    <small class="text-muted">{{ __('socialprofile::messages.court.hints.comment_limit', ['max' => $limits['comment_max']]) }}</small>
                                    @error('comment')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="auto-continued">{{ __('socialprofile::messages.court.fields.continued_case') }}</label>
                                    <input type="number" id="auto-continued" name="continued_case_id" class="form-control @error('continued_case_id') is-invalid @enderror" min="1" placeholder="{{ __('socialprofile::messages.court.placeholders.continued_case') }}" value="{{ old('continued_case_id') }}">
                                    @error('continued_case_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('socialprofile::messages.court.fields.attachments') }}</label>
                                    @for($i = 0; $i < 3; $i++)
                                        <input type="url" name="attachments[]" class="form-control mb-2 @error('attachments.'.$i) is-invalid @enderror" placeholder="{{ __('socialprofile::messages.court.placeholders.attachment', ['number' => $i + 1]) }}">
                                    @endfor
                                    @error('attachments.*')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button class="btn btn-primary w-100">{{ __('socialprofile::messages.court.actions.issue_auto') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('socialprofile::messages.court.manual.title') }}</h5>
                            <small class="text-muted">{{ __('socialprofile::messages.court.manual.subtitle') }}</small>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('socialprofile.court.decisions.manual.store') }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label" for="manual-subject">{{ __('socialprofile::messages.court.fields.subject') }}</label>
                                    <input type="text" id="manual-subject" name="subject" class="form-control @error('subject') is-invalid @enderror" placeholder="{{ __('socialprofile::messages.court.placeholders.subject') }}" value="{{ old('subject') }}" required>
                                    @error('subject')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="manual-comment">{{ __('socialprofile::messages.court.fields.comment') }}</label>
                                    <textarea id="manual-comment" name="comment" maxlength="{{ $limits['comment_max'] }}" class="form-control @error('comment') is-invalid @enderror" rows="4" placeholder="{{ __('socialprofile::messages.court.placeholders.comment') }}" required>{{ old('comment') }}</textarea>
                                    @error('comment')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="manual-socialrating">{{ __('socialprofile::messages.court.fields.socialrating') }}</label>
                                        <input type="number" id="manual-socialrating" name="punishment[socialrating]" class="form-control @error('punishment.socialrating') is-invalid @enderror" placeholder="{{ __('socialprofile::messages.court.placeholders.delta') }}" value="{{ old('punishment.socialrating') }}">
                                        @error('punishment.socialrating')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="manual-activity">{{ __('socialprofile::messages.court.fields.activity') }}</label>
                                        <input type="number" id="manual-activity" name="punishment[activity]" class="form-control @error('punishment.activity') is-invalid @enderror" placeholder="{{ __('socialprofile::messages.court.placeholders.delta') }}" value="{{ old('punishment.activity') }}">
                                        @error('punishment.activity')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row g-3 mt-0">
                                    <div class="col-md-6">
                                        <label class="form-label" for="manual-coins">{{ __('socialprofile::messages.court.fields.coins') }}</label>
                                        <input type="number" id="manual-coins" name="punishment[coins]" class="form-control @error('punishment.coins') is-invalid @enderror" placeholder="{{ __('socialprofile::messages.court.placeholders.delta') }}" value="{{ old('punishment.coins') }}">
                                        @error('punishment.coins')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="manual-money">{{ __('socialprofile::messages.court.fields.money') }}</label>
                                        <input type="number" id="manual-money" name="punishment[money]" class="form-control @error('punishment.money') is-invalid @enderror" placeholder="{{ __('socialprofile::messages.court.placeholders.delta') }}" value="{{ old('punishment.money') }}">
                                        @error('punishment.money')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row g-3 mt-0">
                                    <div class="col-md-6">
                                        <label class="form-label" for="manual-ban">{{ __('socialprofile::messages.court.fields.ban_duration') }}</label>
                                        <input type="text" id="manual-ban" name="ban[duration]" class="form-control @error('ban.duration') is-invalid @enderror" placeholder="{{ __('socialprofile::messages.court.placeholders.duration') }}" value="{{ old('ban.duration') }}">
                                        @error('ban.duration')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="manual-mute">{{ __('socialprofile::messages.court.fields.mute_duration') }}</label>
                                        <input type="text" id="manual-mute" name="mute[duration]" class="form-control @error('mute.duration') is-invalid @enderror" placeholder="{{ __('socialprofile::messages.court.placeholders.duration') }}" value="{{ old('mute.duration') }}">
                                        @error('mute.duration')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="manual-role">{{ __('socialprofile::messages.court.fields.role') }}</label>
                                    <input type="text" id="manual-role" name="role[role_id]" class="form-control @error('role.role_id') is-invalid @enderror" placeholder="{{ __('socialprofile::messages.court.fields.role_placeholder') }}" value="{{ old('role.role_id') }}">
                                    @error('role.role_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="row g-3 mt-0">
                                    <div class="col-md-6">
                                        <label class="form-label" for="manual-role-duration">{{ __('socialprofile::messages.court.fields.role_duration') }}</label>
                                        <input type="text" id="manual-role-duration" name="role[duration]" class="form-control @error('role.duration') is-invalid @enderror" placeholder="{{ __('socialprofile::messages.court.placeholders.duration') }}" value="{{ old('role.duration') }}">
                                        @error('role.duration')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="manual-unverify" name="unverify" value="1" @checked(old('unverify'))>
                                            <label class="form-check-label" for="manual-unverify">{{ __('socialprofile::messages.court.fields.unverify') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="manual-continued">{{ __('socialprofile::messages.court.fields.continued_case') }}</label>
                                    <input type="number" id="manual-continued" name="continued_case_id" class="form-control @error('continued_case_id') is-invalid @enderror" min="1" placeholder="{{ __('socialprofile::messages.court.placeholders.continued_case') }}" value="{{ old('continued_case_id') }}">
                                    @error('continued_case_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('socialprofile::messages.court.fields.attachments') }}</label>
                                    @for($i = 0; $i < 3; $i++)
                                        <input type="url" name="attachments[]" class="form-control mb-2 @error('attachments.'.$i) is-invalid @enderror" placeholder="{{ __('socialprofile::messages.court.placeholders.attachment', ['number' => $i + 1]) }}">
                                    @endfor
                                    @error('attachments.*')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button class="btn btn-outline-primary w-100">{{ __('socialprofile::messages.court.actions.issue_manual') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('socialprofile::messages.court.judge.recent_cases') }}</h5>
                </div>
                <div class="card-body">
                    @forelse($recentCases as $case)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-info text-uppercase">{{ $case->mode }}</span>
                                <small class="text-muted">{{ $case->issued_at?->diffForHumans() }}</small>
                            </div>
                            <h6 class="mt-2 mb-1">{{ $case->case_number }}</h6>
                            <p class="mb-1 text-muted small">{{ \Illuminate\Support\Str::limit($case->comment, 120) }}</p>
                            <div class="small text-muted">
                                {{ __('socialprofile::messages.court.fields.subject') }}: {{ $case->subject->name ?? '—' }}<br>
                                {{ __('socialprofile::messages.court.fields.judge') }}: {{ $case->judge->name ?? '—' }}
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-info mb-0">{{ __('socialprofile::messages.court.history.empty') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
