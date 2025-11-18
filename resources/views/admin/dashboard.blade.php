@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.admin.dashboard.title'))

@push('styles')
<link rel="stylesheet" href="{{ plugin_asset('socialprofile', 'css/style.css') }}">
@endpush

@section('content')
<div class="socialprofile-admin">
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="socialprofile-card">
                <h2>{{ __('socialprofile::messages.admin.dashboard.top_scores') }}</h2>
                <ul class="list-unstyled mb-0">
                    @foreach($topScores as $entry)
                        <li><strong>{{ $entry->user?->name ?? __('socialprofile::messages.leaderboards.unknown') }}</strong> — {{ $entry->score }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="socialprofile-card">
                <h2>{{ __('socialprofile::messages.admin.dashboard.top_activity') }}</h2>
                <ul class="list-unstyled mb-0">
                    @foreach($topActivity as $entry)
                        <li><strong>{{ $entry->user?->name ?? __('socialprofile::messages.leaderboards.unknown') }}</strong> — {{ $entry->points }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="socialprofile-card">
                <h2>{{ __('socialprofile::messages.admin.dashboard.recent_violations') }}</h2>
                <ul class="list-unstyled mb-0">
                    @forelse($recentViolations as $violation)
                        <li>
                            {{ $violation->user?->name ?? __('socialprofile::messages.leaderboards.unknown') }} ·
                            {{ __('socialprofile::messages.violations.types.' . $violation->type) }} ·
                            {{ $violation->points }}
                        </li>
                    @empty
                        <li class="text-muted">{{ __('socialprofile::messages.admin.dashboard.no_violations') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="socialprofile-card">
                <h2>{{ __('socialprofile::messages.admin.dashboard.pending_verifications') }}</h2>
                <ul class="list-unstyled mb-0">
                    @forelse($pendingVerifications as $verification)
                        <li>{{ $verification->user?->name ?? __('socialprofile::messages.leaderboards.unknown') }}</li>
                    @empty
                        <li class="text-muted">{{ __('socialprofile::messages.admin.dashboard.no_pending') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
