@php
    $rating = $rating ?? null;
    $activity = $activity ?? null;
    $stats = $stats ?? null;
    $userId = $user_id ?? uniqid();
    $modalId = 'activity-menu-'.$userId;
@endphp

<div class="socialprofile-card profile-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center">
            <i class="fas fa-running text-primary me-2"></i>
            <div>
                <h3 class="h5 mb-0">{{ __('socialprofile::messages.profile.cards.activity.title') }}</h3>
                <small class="text-muted">{{ __('socialprofile::messages.profile.cards.activity.points') }}</small>
            </div>
        </div>
        @if($rating)
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                {{ __('socialprofile::messages.profile.cards.activity.menu') }}
            </button>
        @else
            <span class="text-muted small">{{ __('socialprofile::messages.progression.ratings.disabled_message') }}</span>
        @endif
    </div>

    <div class="profile-stat-grid">
        <div>
            <span class="label">{{ __('socialprofile::messages.metrics.activity') }}</span>
            <span class="value">{{ number_format($activity->points ?? 0) }}</span>
        </div>
        <div>
            <span class="label">{{ __('socialprofile::messages.metrics.played_minutes') }}</span>
            <span class="value">{{ number_format($stats->played_minutes ?? 0) }}</span>
        </div>
        <div>
            <span class="label">{{ __('socialprofile::messages.metrics.kills') }}</span>
            <span class="value">{{ number_format($stats->kills ?? 0) }}</span>
        </div>
        <div>
            <span class="label">{{ __('socialprofile::messages.metrics.deaths') }}</span>
            <span class="value">{{ number_format($stats->deaths ?? 0) }}</span>
        </div>
    </div>
</div>

@if($rating)
    @include('socialprofile::partials.profile.progress-modal', [
        'modalId' => $modalId,
        'title' => __('socialprofile::messages.profile.cards.activity.menu'),
        'rating' => $rating,
    ])
@endif
