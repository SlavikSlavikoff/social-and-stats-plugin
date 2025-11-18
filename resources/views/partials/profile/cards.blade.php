@php
    $statRows = [
        [
            'label' => __('socialprofile::messages.metrics.social_score'),
            'value' => number_format($score->score ?? 0),
        ],
        [
            'label' => __('socialprofile::messages.metrics.activity'),
            'value' => number_format($activity->points ?? 0),
        ],
        [
            'label' => __('socialprofile::messages.metrics.coins'),
            'value' => number_format($coins->balance ?? 0, 2),
        ],
        [
            'label' => __('socialprofile::messages.metrics.trust'),
            'value' => __('socialprofile::messages.trust.levels.' . ($trust->level ?? 'newbie')),
        ],
        [
            'label' => __('socialprofile::messages.metrics.played_minutes'),
            'value' => number_format($stats->played_minutes ?? 0),
        ],
    ];
    $hasData = collect($statRows)->contains(fn ($row) => ! blank($row['value']));
@endphp

<div class="socialprofile-card stats-card">
    <div class="d-flex align-items-center mb-3">
        <i class="fas fa-chart-line me-2 text-primary"></i>
        <h3 class="h5 mb-0">{{ __('socialprofile::messages.profile.statistics') }}</h3>
    </div>

    @if(! $hasData)
        <p class="text-muted mb-0">{{ __('socialprofile::messages.profile.empty_state') }}</p>
    @else
        <div class="stat-list">
            @foreach($statRows as $row)
                <div class="stat-row d-flex justify-content-between align-items-center">
                    <span class="text-muted small">{{ $row['label'] }}</span>
                    <span class="fw-semibold">{{ $row['value'] }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
