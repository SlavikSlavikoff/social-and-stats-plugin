@php
    $rating = $rating ?? null;
    $score = $score ?? null;
    $trust = $trust ?? null;
    $violations = $violations ?? collect();
    $userId = $user_id ?? uniqid();
    $menuModalId = 'social-menu-'.$userId;
    $violationsModalId = 'social-violations-'.$userId;
    $trustKey = $trust->level ?? 'newbie';
    $trustLabel = __('socialprofile::messages.trust.levels.'.$trustKey);
    $ratingThresholds = is_array($rating) ? ($rating['thresholds'] ?? []) : [];
    $ratingValue = is_array($rating) ? $rating['value'] : null;
@endphp

<div class="socialprofile-card profile-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center">
            <i class="fas fa-users text-success me-2"></i>
            <div>
                <h3 class="h5 mb-0">{{ __('socialprofile::messages.profile.cards.social.title') }}</h3>
                <small class="text-muted">{{ __('socialprofile::messages.profile.trust') }}</small>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($rating)
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $menuModalId }}">
                    {{ __('socialprofile::messages.profile.cards.social.menu') }}
                </button>
            @endif
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $violationsModalId }}">
                {{ __('socialprofile::messages.profile.cards.social.violations') }}
            </button>
        </div>
    </div>

    <div class="profile-stat-grid">
        <div>
            <span class="label">{{ __('socialprofile::messages.profile.cards.social.trust') }}</span>
            <span class="value">{{ $trustLabel }}</span>
        </div>
        <div>
            <span class="label">{{ __('socialprofile::messages.profile.cards.social.rating') }}</span>
            <span class="value">{{ number_format($score->score ?? 0) }}</span>
        </div>
        <div>
            <span class="label">{{ __('socialprofile::messages.profile.progress.thresholds_count') }}</span>
            <span class="value">{{ number_format(count($ratingThresholds)) }}</span>
        </div>
        <div>
            <span class="label">{{ __('socialprofile::messages.profile.progress.current_value') }}</span>
            <span class="value">
                @if($ratingValue !== null)
                    {{ number_format($ratingValue) }}
                @else
                    &mdash;
                @endif
            </span>
        </div>
    </div>
</div>

@if($rating)
    @include('socialprofile::partials.profile.progress-modal', [
        'modalId' => $menuModalId,
        'title' => __('socialprofile::messages.profile.cards.social.menu'),
        'rating' => $rating,
    ])
@endif

<div class="modal fade" id="{{ $violationsModalId }}" tabindex="-1" aria-labelledby="{{ $violationsModalId }}-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $violationsModalId }}-label">{{ __('socialprofile::messages.profile.cards.social.violations') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('socialprofile::messages.profile.modals.close') }}"></button>
            </div>
            <div class="modal-body">
                @include('socialprofile::partials.profile.violations', ['violations' => $violations])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('socialprofile::messages.profile.modals.close') }}</button>
            </div>
        </div>
    </div>
</div>
