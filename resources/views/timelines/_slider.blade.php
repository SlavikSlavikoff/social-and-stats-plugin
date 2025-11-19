@if($timeline && $timeline->periods->isNotEmpty())
    <div class="timeline-slider" data-timeline-slider>
        <button class="timeline-slider-nav timeline-slider-prev" type="button" data-slider-prev aria-label="{{ __('socialprofile::messages.timelines.slider.prev') }}">
            <span class="timeline-slider-arrow" aria-hidden="true"></span>
        </button>
        <div class="timeline-slider-track" data-slider-track>
            @php
                $timelineLocale = app()->getLocale();
                $timelineDash = html_entity_decode('&mdash;', ENT_QUOTES, 'UTF-8');
            @endphp
            @foreach($timeline->periods as $period)
                <section class="timeline-slider-period">
                    @if($timeline->show_period_labels)
                        <div class="timeline-period-header">
                            <h3 class="mb-1">{{ $period->title }}</h3>
                            @if($period->start_date || $period->end_date)
                                <div class="timeline-period-dates text-muted">
                                    {{ $period->start_date?->locale($timelineLocale)->translatedFormat('F Y') ?? $timelineDash }}
                                    <span class="mx-1">&ndash;</span>
                                    {{ $period->end_date
                                        ? $period->end_date->locale($timelineLocale)->translatedFormat('F Y')
                                        : __('socialprofile::messages.timelines.slider.present')
                                    }}
                                </div>
                            @endif
                            @if($period->description)
                                <p class="text-muted small">{{ $period->description }}</p>
                            @endif
                        </div>
                    @endif

                    <div class="timeline-period-cards">
                        @forelse($period->cards as $card)
                            <article class="timeline-card-entry {{ $card->highlight ? 'timeline-card-highlight' : '' }}">
                                @if($card->image_path)
                                    <div class="timeline-card-image">
                                        <img src="{{ Storage::disk('public')->url($card->image_path) }}" alt="{{ $card->title }}">
                                    </div>
                                @endif
                                <div class="timeline-card-content">
                                    <h4>{{ $card->title }}</h4>
                                    @if($card->subtitle)
                                        <p class="text-muted">{{ $card->subtitle }}</p>
                                    @endif
                                    @if(! empty($card->items))
                                        <ul>
                                            @foreach($card->items as $item)
                                                <li>{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if($card->button_label && $card->button_url)
                                        <a href="{{ $card->button_url }}" class="btn btn-outline-primary btn-sm mt-2">
                                            {{ $card->button_label }}
                                        </a>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="timeline-period-empty">{{ __('socialprofile::messages.timelines.slider.empty_period') }}</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
        <button class="timeline-slider-nav timeline-slider-next" type="button" data-slider-next aria-label="{{ __('socialprofile::messages.timelines.slider.next') }}">
            <span class="timeline-slider-arrow" aria-hidden="true"></span>
        </button>
    </div>
@else
    <div class="alert alert-info">
        {{ __('socialprofile::messages.timelines.slider.empty') }}
    </div>
@endif
