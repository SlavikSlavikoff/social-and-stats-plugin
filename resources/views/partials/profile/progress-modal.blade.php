@php
    $rating = $rating ?? null;
    $modalId = $modalId ?? uniqid('progress-modal-');
    $title = $title ?? __('socialprofile::messages.progression.title');
    $visual = $rating['visual'] ?? null;
    $thresholds = $rating['thresholds'] ?? [];
    $visualPercent = $visual['percent'] ?? 0;
    $formatBandValue = function (?int $value, bool $isUpper) {
        return $value === null
            ? ($isUpper ? __('socialprofile::messages.profile.progress.infinity_positive') : __('socialprofile::messages.profile.progress.infinity_negative'))
            : number_format($value);
    };
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}-label">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('socialprofile::messages.profile.modals.close') }}"></button>
            </div>
            <div class="modal-body">
                @if($rating && $visual)
                    <div class="progression-vertical-wrapper">
                        <div class="progression-vertical-bar">
                            <div class="progression-vertical-fill" style="height: {{ number_format($visualPercent, 2) }}%"></div>

                            @if($visual['is_above'] ?? false)
                                <div class="progression-extreme progression-extreme-top">
                                    {{ __('socialprofile::messages.profile.progress.above_max', ['value' => number_format($visual['max'])]) }}
                                </div>
                            @endif

                            @if($visual['is_below'] ?? false)
                                <div class="progression-extreme progression-extreme-bottom">
                                    {{ __('socialprofile::messages.profile.progress.below_min', ['value' => number_format($visual['min'])]) }}
                                </div>
                            @endif

                            @foreach($thresholds as $threshold)
                                @php
                                    $band = $threshold['band'] ?? ['min' => null, 'max' => null];
                                    $bandText = $formatBandValue($band['min'] ?? null, false).' - '.$formatBandValue($band['max'] ?? null, true);
                                    $displayBand = !empty($threshold['is_punishment']) || !empty($threshold['band_configured']);
                                    $markerClasses = trim('progression-marker '
                                        .(!empty($threshold['reached']) ? 'reached ' : '')
                                        .(!empty($threshold['is_punishment']) ? 'punishment ' : '')
                                        .(!empty($threshold['active']) ? 'active ' : ''));
                                    $tooltipParts = array_filter([
                                        $threshold['label'] ?? null,
                                        $threshold['description'] ?? __('socialprofile::messages.profile.progress.no_description'),
                                        $displayBand ? __('socialprofile::messages.profile.progress.band_range', ['range' => $bandText]) : null,
                                        !empty($threshold['is_punishment']) ? __('socialprofile::messages.profile.progress.punishment_badge') : null,
                                    ]);
                                    $tooltip = implode(' · ', $tooltipParts);
                                @endphp
                                <button type="button"
                                        class="{{ $markerClasses }}"
                                        style="bottom: {{ number_format($threshold['visual_position'] ?? $threshold['position'] ?? 0, 2) }}%"
                                        data-bs-toggle="tooltip"
                                        title="{{ $tooltip }}">
                                    <span class="marker-value">{{ number_format($threshold['value']) }}</span>
                                </button>
                            @endforeach

                            <div class="progression-current" style="bottom: {{ number_format($visualPercent, 2) }}%">
                                <span>{{ number_format($rating['value']) }}</span>
                            </div>
                        </div>

                        <div class="progression-vertical-legend d-flex justify-content-between mt-3">
                            <div>
                                <div class="text-muted small">{{ __('socialprofile::messages.profile.progress.legend.min') }}</div>
                                <div class="fw-semibold">{{ number_format($visual['min']) }}</div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted small">{{ __('socialprofile::messages.profile.progress.legend.max') }}</div>
                                <div class="fw-semibold">{{ number_format($visual['max']) }}</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-2 small text-muted flex-wrap gap-2">
                            <span>{{ __('socialprofile::messages.profile.progress.current_value') }}: <strong>{{ number_format($rating['value']) }}</strong></span>
                            @if(($rating['support_points'] ?? 0) > 0)
                                <span>{{ __('socialprofile::messages.progression_support_points', ['value' => number_format($rating['support_points'])]) }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="text-uppercase small text-muted mb-2">{{ __('socialprofile::messages.profile.progress.thresholds_title') }}</h6>
                        @if(empty($thresholds))
                            <p class="text-muted mb-0">{{ __('socialprofile::messages.profile.progress.empty_thresholds') }}</p>
                        @else
                            <ul class="list-unstyled progression-threshold-list mb-0">
                                @foreach($thresholds as $threshold)
                                    @php
                                        $band = $threshold['band'] ?? ['min' => null, 'max' => null];
                                        $bandText = $formatBandValue($band['min'] ?? null, false).' - '.$formatBandValue($band['max'] ?? null, true);
                                        $displayBand = !empty($threshold['is_punishment']) || !empty($threshold['band_configured']);
                                        $itemClasses = trim(($threshold['reached'] ? 'reached ' : '').(!empty($threshold['is_punishment']) ? 'punishment ' : '').(!empty($threshold['is_punishment']) && !empty($threshold['active']) ? 'punishment-active ' : ''));
                                    @endphp
                                    <li class="mb-3 {{ $itemClasses }}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>{{ $threshold['label'] }}</strong>
                                            <span class="text-muted">{{ number_format($threshold['value']) }}</span>
                                        </div>
                                        <p class="text-muted small mb-1">{{ $threshold['description'] ?? __('socialprofile::messages.profile.progress.no_description') }}</p>
                                        @if(!empty($threshold['is_punishment']))
                                            <div class="small {{ !empty($threshold['active']) ? 'text-danger' : 'text-muted' }}">
                                                {{ __('socialprofile::messages.profile.progress.band_range', ['range' => $bandText]) }}
                                                ·
                                                {{ !empty($threshold['active']) ? __('socialprofile::messages.profile.progress.punishment_active') : __('socialprofile::messages.profile.progress.punishment_inactive') }}
                                            </div>
                                        @elseif($displayBand)
                                            <div class="small text-muted">{{ __('socialprofile::messages.profile.progress.band_range', ['range' => $bandText]) }}</div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @else
                    <p class="text-muted mb-0">{{ __('socialprofile::messages.profile.progress.empty_thresholds') }}</p>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('socialprofile::messages.profile.modals.close') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById(@json($modalId));
            if (!modal || typeof bootstrap === 'undefined') {
                return;
            }

            modal.addEventListener('shown.bs.modal', function () {
                modal.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                    bootstrap.Tooltip.getOrCreateInstance(el);
                });
            });
        });
    </script>
@endpush
