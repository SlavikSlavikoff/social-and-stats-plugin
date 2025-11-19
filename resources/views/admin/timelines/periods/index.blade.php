<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="mb-1">{{ __('socialprofile::messages.admin.timelines.periods.title') }}</h4>
        <p class="mb-0 text-muted">{{ __('socialprofile::messages.admin.timelines.periods.description') }}</p>
    </div>
    <a href="{{ route('socialprofile.admin.timelines.periods.create', $timeline) }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> {{ __('socialprofile::messages.admin.timelines.periods.actions.create') }}
    </a>
</div>

@if($periods->isEmpty())
    <div class="alert alert-info">
        {{ __('socialprofile::messages.admin.timelines.periods.empty') }}
    </div>
@else
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="text-center">{{ __('socialprofile::messages.admin.timelines.fields.order') }}</th>
                    <th>{{ __('socialprofile::messages.admin.timelines.fields.title') }}</th>
                    <th>{{ __('socialprofile::messages.admin.timelines.periods.dates') }}</th>
                    <th>{{ __('socialprofile::messages.admin.timelines.fields.cards') }}</th>
                    <th class="text-end">{{ __('socialprofile::messages.admin.timelines.fields.actions') }}</th>
                </tr>
            </thead>
            <tbody data-period-sortable data-order-endpoint="{{ route('socialprofile.admin.timelines.periods.order', $timeline) }}" data-order-error="{{ __('socialprofile::messages.admin.timelines.order_failed') }}">
                @foreach($periods as $period)
                    <tr data-item-id="{{ $period->id }}" draggable="true">
                        <td class="text-center">
                            <span class="drag-handle" title="{{ __('socialprofile::messages.admin.timelines.periods.drag_help') }}">
                                <i class="fas fa-grip-lines"></i>
                            </span>
                        </td>
                        <td>
                            <strong>{{ $period->title }}</strong>
                            @if($period->description)
                                <div class="text-muted small">{{ $period->description }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="small text-muted">
                                {{ $period->start_date?->format('Y-m-d') ?? '—' }} &rarr; {{ $period->end_date?->format('Y-m-d') ?? '—' }}
                            </div>
                        </td>
                        <td>{{ $period->cards_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('socialprofile.admin.timelines.periods.edit', [$timeline, $period]) }}" class="btn btn-sm btn-outline-primary">
                                {{ __('messages.actions.edit') }}
                            </a>
                            <form action="{{ route('socialprofile.admin.timelines.periods.destroy', [$timeline, $period]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('socialprofile::messages.admin.timelines.periods.confirm_delete') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    {{ __('messages.actions.delete') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
