<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="mb-1">{{ __('socialprofile::messages.admin.timelines.cards.title') }}</h4>
        <p class="mb-0 text-muted">{{ __('socialprofile::messages.admin.timelines.cards.description') }}</p>
    </div>
    <a href="{{ route('socialprofile.admin.timelines.cards.create', $timeline) }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> {{ __('socialprofile::messages.admin.timelines.cards.actions.create') }}
    </a>
</div>

<form method="GET" action="{{ route('socialprofile.admin.timelines.edit', $timeline) }}" class="row g-3 align-items-end mb-4">
    <input type="hidden" name="tab" value="cards">
    <div class="col-md-4">
        <label class="form-label" for="filter-period">{{ __('socialprofile::messages.admin.timelines.cards.filters.period') }}</label>
        <select class="form-select" id="filter-period" name="period_id">
            <option value="">{{ __('socialprofile::messages.admin.timelines.cards.filters.all_periods') }}</option>
            @foreach($periods as $period)
                <option value="{{ $period->id }}" @selected($filters['period_id'] === $period->id)>{{ $period->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="filter-visibility">{{ __('socialprofile::messages.admin.timelines.cards.filters.visibility') }}</label>
        <select class="form-select" id="filter-visibility" name="visibility">
            <option value="">{{ __('socialprofile::messages.admin.timelines.cards.filters.any_visibility') }}</option>
            <option value="visible" @selected($filters['visibility'] === 'visible')>{{ __('socialprofile::messages.admin.timelines.cards.badges.visible') }}</option>
            <option value="hidden" @selected($filters['visibility'] === 'hidden')>{{ __('socialprofile::messages.admin.timelines.cards.badges.hidden') }}</option>
        </select>
    </div>
    <div class="col-md-4 d-flex gap-2">
        <button type="submit" class="btn btn-outline-primary align-self-end">
            {{ __('socialprofile::messages.admin.timelines.cards.actions.apply_filters') }}
        </button>
        <a href="{{ route('socialprofile.admin.timelines.edit', ['timeline' => $timeline, 'tab' => 'cards']) }}" class="btn btn-outline-secondary align-self-end">
            {{ __('socialprofile::messages.admin.timelines.cards.actions.reset_filters') }}
        </a>
    </div>
</form>

@if($periods->isEmpty())
    <div class="alert alert-warning">
        {{ __('socialprofile::messages.admin.timelines.cards.no_periods_warning') }}
    </div>
@else
    <div class="timeline-card-board" data-card-sortable data-order-endpoint="{{ route('socialprofile.admin.timelines.cards.order', $timeline) }}" data-order-error="{{ __('socialprofile::messages.admin.timelines.order_failed') }}">
        @foreach($periods as $period)
            <div class="timeline-card-column">
                <div class="timeline-card-column-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">{{ $period->title }}</h5>
                        <small class="text-muted">{{ trans_choice('socialprofile::messages.admin.timelines.cards.count', $period->cards_count, ['count' => $period->cards_count]) }}</small>
                    </div>
                    <a href="{{ route('socialprofile.admin.timelines.cards.create', $timeline) }}?period_id={{ $period->id }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
                <div class="timeline-card-list"
                    data-card-dropzone
                    data-period-id="{{ $period->id }}">
                    @forelse(($cards[$period->id] ?? collect()) as $card)
                        <div class="card timeline-card-item mb-3 {{ $card->highlight ? 'timeline-card-item-highlight' : '' }}" data-card-id="{{ $card->id }}" draggable="true">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">{{ $card->title }}</h6>
                                        @if($card->subtitle)
                                            <div class="text-muted small">{{ $card->subtitle }}</div>
                                        @endif
                                    </div>
                                    <div class="text-end">
                                        <span class="badge {{ $card->is_visible ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $card->is_visible
                                                ? __('socialprofile::messages.admin.timelines.cards.badges.visible')
                                                : __('socialprofile::messages.admin.timelines.cards.badges.hidden') }}
                                        </span>
                                        @if($card->highlight)
                                            <span class="badge bg-warning text-dark">{{ __('socialprofile::messages.admin.timelines.cards.badges.highlight') }}</span>
                                        @endif
                                    </div>
                                </div>
                                @if(! empty($card->items))
                                    <ul class="small text-muted ps-3">
                                        @foreach(array_slice($card->items, 0, 2) as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                <div class="d-flex gap-2 mt-3">
                                    <a href="{{ route('socialprofile.admin.timelines.cards.edit', [$timeline, $card]) }}" class="btn btn-sm btn-outline-primary">
                                        {{ __('messages.actions.edit') }}
                                    </a>
                                    <form action="{{ route('socialprofile.admin.timelines.cards.destroy', [$timeline, $card]) }}" method="POST" onsubmit="return confirm('{{ __('socialprofile::messages.admin.timelines.cards.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            {{ __('messages.actions.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-light">
                            {{ __('socialprofile::messages.admin.timelines.cards.empty_period') }}
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
@endif
