@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.progression.thresholds.title', ['rating' => $rating->name]))

@section('content')
<div class="socialprofile-admin">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <a href="{{ route('socialprofile.admin.progression.index') }}" class="btn btn-link mb-3">&larr; {{ __('socialprofile::messages.progression.back_to_ratings') }}</a>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="socialprofile-card">
                <h3>{{ __('socialprofile::messages.progression.thresholds.create') }}</h3>
                <form method="POST" action="{{ route('socialprofile.admin.progression.thresholds.store', $rating) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.label') }}</label>
                        <input type="text" name="label" class="form-control" placeholder="{{ __('socialprofile::messages.progression.thresholds.placeholders.label') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.value') }}</label>
                        <input type="number" name="value" class="form-control" placeholder="{{ __('socialprofile::messages.progression.thresholds.placeholders.value') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.description') }}</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="{{ __('socialprofile::messages.progression.thresholds.placeholders.description') }}"></textarea>
                    </div>
                    <div class="row">
                        <div class="col">
                            <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.color') }}</label>
                            <input type="text" name="color" class="form-control" placeholder="{{ __('socialprofile::messages.progression.thresholds.placeholders.color') }}">
                        </div>
                        <div class="col">
                            <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.icon') }}</label>
                            <input type="text" name="icon" class="form-control" placeholder="{{ __('socialprofile::messages.progression.thresholds.placeholders.icon') }}">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.direction') }}</label>
                        <select name="direction" class="form-select">
                            <option value="ascend">{{ __('socialprofile::messages.progression.thresholds.directions.ascend') }}</option>
                            <option value="descend">{{ __('socialprofile::messages.progression.thresholds.directions.descend') }}</option>
                            <option value="any">{{ __('socialprofile::messages.progression.thresholds.directions.any') }}</option>
                        </select>
                    </div>
                    <div class="form-check mt-3">
                        <input type="checkbox" name="is_punishment" class="form-check-input" value="1" id="threshold-punishment">
                        <label class="form-check-label" for="threshold-punishment">{{ __('socialprofile::messages.progression.thresholds.is_punishment') }}</label>
                        <small class="text-muted d-block">{{ __('socialprofile::messages.progression.thresholds.is_punishment_hint') }}</small>
                    </div>
                    <div class="row mt-3">
                        <div class="col">
                            <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.band_min') }}</label>
                            <input type="number" name="band_min" class="form-control" placeholder="{{ __('socialprofile::messages.progression.thresholds.placeholders.band_min') }}">
                        </div>
                        <div class="col">
                            <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.band_max') }}</label>
                            <input type="number" name="band_max" class="form-control" placeholder="{{ __('socialprofile::messages.progression.thresholds.placeholders.band_max') }}">
                        </div>
                    </div>
                    <small class="text-muted">{{ __('socialprofile::messages.progression.thresholds.band_hint') }}</small>
                    <div class="mt-3">
                        <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.position') }}</label>
                        <input type="number" name="position" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-check mt-3">
                        <input type="checkbox" name="is_major" class="form-check-input" value="1" id="threshold-major">
                        <label class="form-check-label" for="threshold-major">{{ __('socialprofile::messages.progression.thresholds.is_major') }}</label>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.metadata_json') }}</label>
                        <textarea name="metadata_json" class="form-control" rows="2" placeholder="{{ __('socialprofile::messages.progression.thresholds.placeholders.metadata') }}"></textarea>
                        <small class="text-muted">{{ __('socialprofile::messages.progression.thresholds.metadata_hint') }}</small>
                    </div>
                    <button class="btn btn-primary mt-3">{{ __('socialprofile::messages.actions.create') }}</button>
                </form>
            </div>
        </div>
        <div class="col-lg-8">
            @foreach($thresholds as $threshold)
                <div class="socialprofile-card mb-4">
                    <form method="POST" action="{{ route('socialprofile.admin.progression.thresholds.update', [$rating, $threshold]) }}">
                        @csrf
                        @method('PUT')
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="mb-0">
                                    {{ $threshold->label }}
                                    <small class="text-muted">({{ $threshold->value }})</small>
                                    @if($threshold->is_punishment)
                                        <span class="badge bg-danger">{{ __('socialprofile::messages.progression.thresholds.badges.punishment') }}</span>
                                    @endif
                                </h4>
                                @if($threshold->is_punishment || $threshold->band_min !== null || $threshold->band_max !== null)
                                    @php($bandDisplay = sprintf('%s - %s',
                                        $threshold->band_min === null ? __('socialprofile::messages.profile.progress.infinity_negative') : number_format($threshold->band_min),
                                        $threshold->band_max === null ? __('socialprofile::messages.profile.progress.infinity_positive') : number_format($threshold->band_max)
                                    ))
                                    <div class="text-muted small mt-1">{{ __('socialprofile::messages.progression.thresholds.band_preview', ['range' => $bandDisplay]) }}</div>
                                @endif
                            </div>
                            <div>
                                <button class="btn btn-sm btn-primary">{{ __('socialprofile::messages.actions.update') }}</button>
                                <form action="{{ route('socialprofile.admin.progression.thresholds.destroy', [$rating, $threshold]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('socialprofile::messages.progression.thresholds.confirm_delete') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">&times;</button>
                                </form>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.value') }}</label>
                                <input type="number" class="form-control" name="value" value="{{ $threshold->value }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.direction') }}</label>
                                <select name="direction" class="form-select">
                                    @foreach(['ascend','descend','any'] as $dir)
                                        <option value="{{ $dir }}" @selected($threshold->direction === $dir)>{{ __('socialprofile::messages.progression.thresholds.directions.'.$dir) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.position') }}</label>
                                <input type="number" class="form-control" name="position" value="{{ $threshold->position }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_major" value="1" id="major-{{ $threshold->id }}" @checked($threshold->is_major)>
                                    <label class="form-check-label" for="major-{{ $threshold->id }}">{{ __('socialprofile::messages.progression.thresholds.is_major') }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3 g-3">
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_punishment" value="1" id="punishment-{{ $threshold->id }}" @checked($threshold->is_punishment)>
                                    <label class="form-check-label" for="punishment-{{ $threshold->id }}">{{ __('socialprofile::messages.progression.thresholds.is_punishment') }}</label>
                                    <small class="text-muted d-block">{{ __('socialprofile::messages.progression.thresholds.is_punishment_hint') }}</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.band_min') }}</label>
                                <input type="number" class="form-control" name="band_min" value="{{ $threshold->band_min }}" placeholder="{{ __('socialprofile::messages.progression.thresholds.placeholders.band_min') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.band_max') }}</label>
                                <input type="number" class="form-control" name="band_max" value="{{ $threshold->band_max }}" placeholder="{{ __('socialprofile::messages.progression.thresholds.placeholders.band_max') }}">
                            </div>
                        </div>
                        <small class="text-muted">{{ __('socialprofile::messages.progression.thresholds.band_hint') }}</small>
                        <div class="mt-3">
                            <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.label') }}</label>
                            <input type="text" class="form-control" name="label" value="{{ $threshold->label }}">
                        </div>
                        <div class="mt-3">
                            <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.description') }}</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="{{ __('socialprofile::messages.progression.thresholds.placeholders.description') }}">{{ $threshold->description }}</textarea>
                        </div>
                        <div class="row mt-3">
                            <div class="col">
                                <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.color') }}</label>
                                <input type="text" name="color" class="form-control" value="{{ $threshold->color }}" placeholder="{{ __('socialprofile::messages.progression.thresholds.placeholders.color') }}">
                            </div>
                            <div class="col">
                                <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.icon') }}</label>
                                <input type="text" name="icon" class="form-control" value="{{ $threshold->icon }}" placeholder="{{ __('socialprofile::messages.progression.thresholds.placeholders.icon') }}">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">{{ __('socialprofile::messages.progression.thresholds.metadata_json') }}</label>
                            <textarea name="metadata_json" class="form-control" rows="2" placeholder="{{ __('socialprofile::messages.progression.thresholds.placeholders.metadata') }}">{{ json_encode($threshold->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                        </div>
                    </form>

                    <hr>

                    <h5>{{ __('socialprofile::messages.progression.actions.heading') }}</h5>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                            <tr>
                                <th>{{ __('socialprofile::messages.progression.actions.type') }}</th>
                                <th>{{ __('socialprofile::messages.progression.actions.details') }}</th>
                                <th>{{ __('socialprofile::messages.progression.actions.auto_revert') }}</th>
                                <th class="text-end">{{ __('socialprofile::messages.actions.edit') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($threshold->actions as $action)
                                <tr>
                                    <td>{{ __('socialprofile::messages.progression.actions.types.'.$action->action) }}</td>
                                    <td><pre class="mb-0 small">{{ json_encode($action->config ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></td>
                                    <td>{{ $action->auto_revert ? __('socialprofile::messages.yes') : __('socialprofile::messages.no') }}</td>
                                    <td class="text-end">
                                        <details>
                                            <summary class="btn btn-sm btn-outline-primary">{{ __('socialprofile::messages.actions.edit') }}</summary>
                                        <form method="POST" action="{{ route('socialprofile.admin.progression.actions.update', $action) }}" class="mt-2">
                                            @csrf
                                            @method('PUT')
                                            @include('socialprofile::admin.progression.partials.action-fields', ['action' => $action, 'integrations' => $integrations, 'threshold' => $threshold])
                                            <button class="btn btn-primary btn-sm mt-2">{{ __('socialprofile::messages.actions.update') }}</button>
                                        </form>
                                            <form method="POST" action="{{ route('socialprofile.admin.progression.actions.destroy', $action) }}" class="mt-2" onsubmit="return confirm('{{ __('socialprofile::messages.progression.actions.confirm_delete') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm">{{ __('socialprofile::messages.actions.delete') }}</button>
                                            </form>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">{{ __('socialprofile::messages.progression.actions.empty') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6 class="mt-3">{{ __('socialprofile::messages.progression.actions.add') }}</h6>
                    <form method="POST" action="{{ route('socialprofile.admin.progression.actions.store', $threshold) }}">
                        @csrf
                        @include('socialprofile::admin.progression.partials.action-fields', ['action' => null, 'integrations' => $integrations, 'threshold' => $threshold])
                        <button class="btn btn-secondary btn-sm mt-2">{{ __('socialprofile::messages.progression.actions.add_button') }}</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('[data-action-selector]').forEach(function (select) {
        const container = select.closest('[data-action-container]');

        function toggleFields() {
            const type = select.value;
            container.querySelectorAll('[data-action-fields]').forEach(function (block) {
                const match = block.dataset.actionFields === type || block.dataset.actionFieldsAlt === type;
                block.classList.toggle('d-none', !match);
            });
        }

        select.addEventListener('change', toggleFields);
        toggleFields();
    });
</script>
@endpush
@endsection

