@extends('layouts.app')

@section('title', __('socialprofile::messages.profile.title'))

@push('styles')
<link rel="stylesheet" href="{{ plugin_asset('socialprofile', 'css/style.css') }}">
@endpush

@section('content')
<div class="container socialprofile-container">
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="socialprofile-card">
                <h2 class="card-title">{{ __('socialprofile::messages.profile.identity') }}</h2>
                <div class="d-flex align-items-center">
                    <img src="{{ method_exists($user, 'getAvatar') ? $user->getAvatar() : $user->avatar_url ?? 'https://via.placeholder.com/96' }}" class="avatar me-3" alt="{{ $user->name }}">
                    <div>
                        <h3 class="mb-1">{{ $user->name }}</h3>
                    </div>
                </div>
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
                @php($canSeeCoins = true)
                <div>
                    <span class="metric-label">{{ __('socialprofile::messages.metrics.coins') }}</span>
                    <span class="metric-value">{{ $canSeeCoins ? number_format($coins->balance, 2) : '—' }}</span>
                </div>
                <div>
                    <span class="metric-label">{{ __('socialprofile::messages.metrics.trust') }}</span>
                    <span class="metric-value">{{ __('socialprofile::messages.trust.levels.' . $trust->level) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="socialprofile-card">
                <h3>{{ __('socialprofile::messages.profile.statistics') }}</h3>
                <ul class="list-unstyled mb-0">
                    <li>{{ __('socialprofile::messages.metrics.played_minutes') }}: <strong>{{ $stats->played_minutes }}</strong></li>
                    <li>{{ __('socialprofile::messages.metrics.kills') }}: <strong>{{ $stats->kills }}</strong></li>
                    <li>{{ __('socialprofile::messages.metrics.deaths') }}: <strong>{{ $stats->deaths }}</strong></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="socialprofile-card">
                <h3>{{ __('socialprofile::messages.profile.recent_violations') }}</h3>
                @if($violations->isEmpty())
                    <p class="mb-0 text-muted">{{ __('socialprofile::messages.profile.no_violations') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('socialprofile::messages.profile.type') }}</th>
                                    <th>{{ __('socialprofile::messages.profile.reason') }}</th>
                                    <th>{{ __('socialprofile::messages.profile.points') }}</th>
                                    <th>{{ __('socialprofile::messages.profile.date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($violations as $violation)
                                    <tr>
                                        <td>{{ __('socialprofile::messages.violations.types.' . $violation->type) }}</td>
                                        <td>{{ $violation->reason }}</td>
                                        <td>{{ $violation->points }}</td>
                                        <td>{{ $violation->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
