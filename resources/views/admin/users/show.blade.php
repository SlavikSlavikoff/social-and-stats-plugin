@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.admin.users.profile', ['user' => $user->name]))

@push('styles')
<link rel="stylesheet" href="{{ plugin_asset('socialprofile', 'css/style.css') }}">
@endpush

@section('content')
<div class="socialprofile-admin">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="socialprofile-card">
                <h2>{{ $user->name }}</h2>
                <p class="text-muted mb-0">{{ __('socialprofile::messages.admin.users.trust_level') }}: {{ __('socialprofile::messages.trust.levels.' . $trust->level) }}</p>
            </div>
        </div>
        <div class="col-md-8">
            <div class="socialprofile-card metric-grid">
                <div>
                    <span class="metric-label">{{ __('socialprofile::messages.metrics.social_score') }}</span>
                    <span class="metric-value">{{ $score->score }}</span>
                </div>
                <div>
                    <span class="metric-label">{{ __('socialprofile::messages.metrics.activity') }}</span>
                    <span class="metric-value">{{ $activity->points }}</span>
                </div>
                <div>
                    <span class="metric-label">{{ __('socialprofile::messages.metrics.coins') }}</span>
                    <span class="metric-value">{{ number_format($coins->balance, 2) }}</span>
                </div>
                <div>
                    <span class="metric-label">{{ __('socialprofile::messages.metrics.played_minutes') }}</span>
                    <span class="metric-value">{{ $stats->played_minutes }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="socialprofile-card">
                <h3>{{ __('socialprofile::messages.admin.users.edit_metrics') }}</h3>
                <form method="POST" action="{{ route('socialprofile.admin.users.metrics.update', $user) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.metrics.social_score') }}</label>
                        <input type="number" name="score" class="form-control" min="0" value="{{ old('score', $score->score) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.metrics.activity') }}</label>
                        <input type="number" name="activity" class="form-control" min="0" value="{{ old('activity', $activity->points) }}">
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="mb-3">
                                <label class="form-label">{{ __('socialprofile::messages.metrics.coins') }}</label>
                                <input type="number" name="balance" class="form-control" step="0.01" min="0" value="{{ old('balance', $coins->balance) }}">
                            </div>
                        </div>
                        <div class="col">
                            <div class="mb-3">
                                <label class="form-label">{{ __('socialprofile::messages.metrics.hold') }}</label>
                                <input type="number" name="hold" class="form-control" step="0.01" min="0" value="{{ old('hold', $coins->hold) }}">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.metrics.played_minutes') }}</label>
                        <input type="number" name="played_minutes" class="form-control" min="0" value="{{ old('played_minutes', $stats->played_minutes) }}">
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="mb-3">
                                <label class="form-label">{{ __('socialprofile::messages.metrics.kills') }}</label>
                                <input type="number" name="kills" class="form-control" min="0" value="{{ old('kills', $stats->kills) }}">
                            </div>
                        </div>
                        <div class="col">
                            <div class="mb-3">
                                <label class="form-label">{{ __('socialprofile::messages.metrics.deaths') }}</label>
                                <input type="number" name="deaths" class="form-control" min="0" value="{{ old('deaths', $stats->deaths) }}">
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">{{ __('socialprofile::messages.admin.users.save') }}</button>
                </form>
            </div>
        </div>
        <div class="col-md-6">
            <div class="socialprofile-card mb-4">
                <h3>{{ __('socialprofile::messages.admin.users.trust_title') }}</h3>
                @can('social.grant_trust')
                <form method="POST" action="{{ route('socialprofile.admin.users.trust.update', $user) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.admin.users.trust_level') }}</label>
                        <select name="level" class="form-select">
                            @foreach($trustLevels as $level)
                                <option value="{{ $level }}" @selected($trust->level === $level)>{{ __('socialprofile::messages.trust.levels.' . $level) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.admin.users.note') }}</label>
                        <input type="text" name="note" class="form-control" value="{{ old('note', $trust->note) }}">
                    </div>
                    <button class="btn btn-primary" type="submit">{{ __('socialprofile::messages.admin.users.save') }}</button>
                </form>
                @else
                    <p class="text-muted mb-0">{{ __('socialprofile::messages.admin.users.permission_required') }}</p>
                @endcan
            </div>
        </div>
    </div>

    <div class="socialprofile-card mt-4">
        <h3>{{ __('socialprofile::messages.admin.users.violations_history') }}</h3>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                <tr>
                    <th>{{ __('socialprofile::messages.profile.type') }}</th>
                    <th>{{ __('socialprofile::messages.profile.reason') }}</th>
                    <th>{{ __('socialprofile::messages.profile.points') }}</th>
                    <th>{{ __('socialprofile::messages.profile.date') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($violations as $violation)
                    <tr>
                        <td>{{ __('socialprofile::messages.violations.types.' . $violation->type) }}</td>
                        <td>{{ $violation->reason }}</td>
                        <td>{{ $violation->points }}</td>
                        <td>{{ $violation->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-muted">{{ __('socialprofile::messages.profile.no_violations') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @can('social.moderate_violations')
        <hr>
        <form method="POST" action="{{ route('socialprofile.admin.users.violations.store', $user) }}" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label">{{ __('socialprofile::messages.profile.type') }}</label>
                <select name="type" class="form-select">
                    @foreach(['warning','mute','ban','other'] as $type)
                        <option value="{{ $type }}">{{ __('socialprofile::messages.violations.types.' . $type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">{{ __('socialprofile::messages.profile.reason') }}</label>
                <input type="text" name="reason" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('socialprofile::messages.profile.points') }}</label>
                <input type="number" name="points" class="form-control" min="0" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('socialprofile::messages.admin.users.evidence') }}</label>
                <input type="url" name="evidence_url" class="form-control">
            </div>
            <div class="col-12">
                <button class="btn btn-danger" type="submit">{{ __('socialprofile::messages.admin.users.add_violation') }}</button>
            </div>
        </form>
        @endcan
    </div>
</div>
@endsection
