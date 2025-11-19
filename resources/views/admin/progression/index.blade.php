@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.progression.title'))

@section('content')
<div class="socialprofile-admin">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="progression-config-tab" data-bs-toggle="tab" data-bs-target="#progression-config" type="button" role="tab" aria-controls="progression-config" aria-selected="true">
                {{ __('socialprofile::messages.progression.tabs.configuration') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="progression-docs-tab" data-bs-toggle="tab" data-bs-target="#progression-docs" type="button" role="tab" aria-controls="progression-docs" aria-selected="false">
                {{ __('socialprofile::messages.progression.tabs.documentation') }}
            </button>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="progression-config" role="tabpanel" aria-labelledby="progression-config-tab">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="socialprofile-card">
                        <h3 class="d-flex justify-content-between align-items-center">
                            <span>{{ __('socialprofile::messages.progression.ratings.heading') }}</span>
                            <a href="{{ route('socialprofile.admin.progression.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('socialprofile::messages.progression.ratings.reset') }}</a>
                        </h3>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                <tr>
                                    <th>{{ __('socialprofile::messages.progression.ratings.name') }}</th>
                                    <th>{{ __('socialprofile::messages.progression.ratings.type') }}</th>
                                    <th>{{ __('socialprofile::messages.progression.ratings.scale') }}</th>
                                    <th>{{ __('socialprofile::messages.progression.ratings.status') }}</th>
                                    <th class="text-end"></th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($ratings as $rating)
                                    <tr>
                                        <td>
                                            <strong>{{ $rating->name }}</strong>
                                            <div class="text-muted small">{{ $rating->slug }}</div>
                                        </td>
                                        <td>{{ __('socialprofile::messages.progression.ratings.types.'.$rating->type) }}</td>
                                        <td>{{ $rating->scale_min }} &rarr; {{ $rating->scale_max }}</td>
                                        <td>
                                            <span class="badge {{ $rating->is_enabled ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $rating->is_enabled ? __('socialprofile::messages.progression.ratings.enabled') : __('socialprofile::messages.progression.ratings.disabled') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('socialprofile.admin.progression.index', ['edit' => $rating->id]) }}" class="btn btn-outline-primary btn-sm">
                                                {{ __('socialprofile::messages.actions.edit') }}
                                            </a>
                                            <a href="{{ route('socialprofile.admin.progression.thresholds.index', $rating) }}" class="btn btn-outline-secondary btn-sm">
                                                {{ __('socialprofile::messages.progression.thresholds.manage') }}
                                            </a>
                                            <a href="{{ route('socialprofile.admin.progression.rules.index', $rating) }}" class="btn btn-outline-secondary btn-sm">
                                                {{ __('socialprofile::messages.progression.rules.manage') }}
                                            </a>
                                            <form action="{{ route('socialprofile.admin.progression.ratings.destroy', $rating) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('socialprofile::messages.progression.ratings.confirm_delete') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm">&times;</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">{{ __('socialprofile::messages.progression.ratings.empty') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="socialprofile-card">
                        <h3>{{ $editingRating ? __('socialprofile::messages.progression.ratings.editing', ['name' => $editingRating->name]) : __('socialprofile::messages.progression.ratings.create') }}</h3>
                        <form method="POST" action="{{ $editingRating ? route('socialprofile.admin.progression.ratings.update', $editingRating) : route('socialprofile.admin.progression.ratings.store') }}">
                            @csrf
                            @if($editingRating)
                                @method('PUT')
                            @endif
                            <div class="mb-3">
                                <label class="form-label">{{ __('socialprofile::messages.progression.ratings.name') }}</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $editingRating->name ?? '') }}" placeholder="{{ __('socialprofile::messages.progression.placeholders.name') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('socialprofile::messages.progression.ratings.slug') }}</label>
                                <input type="text" name="slug" class="form-control" value="{{ old('slug', $editingRating->slug ?? '') }}" placeholder="{{ __('socialprofile::messages.progression.placeholders.slug') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('socialprofile::messages.progression.ratings.description') }}</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="{{ __('socialprofile::messages.progression.placeholders.description') }}">{{ old('description', $editingRating->description ?? '') }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('socialprofile::messages.progression.ratings.type') }}</label>
                                <select name="type" class="form-select">
                                    @foreach(['social','activity','custom'] as $type)
                                        <option value="{{ $type }}" @selected(old('type', $editingRating->type ?? 'custom') === $type)>
                                            {{ __('socialprofile::messages.progression.ratings.types.'.$type) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <label class="form-label">{{ __('socialprofile::messages.progression.ratings.scale_min') }}</label>
                                    <input type="number" name="scale_min" class="form-control" value="{{ old('scale_min', $editingRating->scale_min ?? 0) }}" placeholder="{{ __('socialprofile::messages.progression.placeholders.scale_min') }}" required>
                                </div>
                                <div class="col">
                                    <label class="form-label">{{ __('socialprofile::messages.progression.ratings.scale_max') }}</label>
                                    <input type="number" name="scale_max" class="form-control" value="{{ old('scale_max', $editingRating->scale_max ?? 100) }}" placeholder="{{ __('socialprofile::messages.progression.placeholders.scale_max') }}" required>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <label class="form-label">{{ __('socialprofile::messages.progression.ratings.visual_min') }}</label>
                                    <input type="number" name="visual_min" class="form-control" value="{{ old('visual_min', $editingRating->settings['visual_min'] ?? '') }}" placeholder="{{ __('socialprofile::messages.progression.placeholders.visual_min') }}">
                                    <small class="text-muted">{{ __('socialprofile::messages.progression.ratings.visual_hint') }}</small>
                                </div>
                                <div class="col">
                                <label class="form-label">{{ __('socialprofile::messages.progression.ratings.visual_max') }}</label>
                                    <input type="number" name="visual_max" class="form-control" value="{{ old('visual_max', $editingRating->settings['visual_max'] ?? '') }}" placeholder="{{ __('socialprofile::messages.progression.placeholders.visual_max') }}">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <label class="form-label">{{ __('socialprofile::messages.progression.ratings.color') }}</label>
                                    <input type="text" name="color" class="form-control" value="{{ old('color', $editingRating->settings['color'] ?? '#38b2ac') }}" placeholder="{{ __('socialprofile::messages.progression.placeholders.color') }}">
                                </div>
                                <div class="col">
                                    <label class="form-label">{{ __('socialprofile::messages.progression.ratings.unit') }}</label>
                                    <input type="text" name="unit" class="form-control" value="{{ old('unit', $editingRating->settings['unit'] ?? '') }}" placeholder="{{ __('socialprofile::messages.progression.placeholders.unit') }}">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <label class="form-label">{{ __('socialprofile::messages.progression.ratings.display_zero') }}</label>
                                    <input type="number" name="display_zero" class="form-control" value="{{ old('display_zero', $editingRating->settings['display_zero'] ?? 0) }}" placeholder="{{ __('socialprofile::messages.progression.placeholders.display_zero') }}">
                                </div>
                                <div class="col">
                                    <label class="form-label">{{ __('socialprofile::messages.progression.ratings.sort_order') }}</label>
                                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $editingRating->sort_order ?? '') }}" placeholder="{{ __('socialprofile::messages.progression.placeholders.sort_order') }}">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <label class="form-label">{{ __('socialprofile::messages.progression.ratings.support_threshold') }}</label>
                                    <input type="number" name="support_threshold" class="form-control" value="{{ old('support_threshold', $editingRating->settings['support_threshold'] ?? '') }}" placeholder="{{ __('socialprofile::messages.progression.placeholders.support_threshold') }}">
                                    <small class="text-muted">{{ __('socialprofile::messages.progression.ratings.support_threshold_hint') }}</small>
                                </div>
                                <div class="col">
                                    <label class="form-label">{{ __('socialprofile::messages.progression.ratings.support_meta_key') }}</label>
                                    <input type="text" name="support_meta_key" class="form-control" value="{{ old('support_meta_key', $editingRating->settings['support_meta_key'] ?? 'support_points') }}" placeholder="{{ __('socialprofile::messages.progression.placeholders.support_meta_key') }}">
                                </div>
                            </div>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" value="1" name="is_enabled" id="rating-enabled" @checked(old('is_enabled', $editingRating->is_enabled ?? true))>
                                <label class="form-check-label" for="rating-enabled">{{ __('socialprofile::messages.progression.ratings.enabled_toggle') }}</label>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button class="btn btn-primary" type="submit">
                                    {{ $editingRating ? __('socialprofile::messages.actions.update') : __('socialprofile::messages.actions.create') }}
                                </button>
                                @if($editingRating)
                                    <a href="{{ route('socialprofile.admin.progression.index') }}" class="btn btn-secondary">{{ __('socialprofile::messages.actions.cancel') }}</a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="progression-docs" role="tabpanel" aria-labelledby="progression-docs-tab">
            <div class="socialprofile-card mb-4">
                <h3>{{ __('socialprofile::messages.progression.docs.overview.title') }}</h3>
                <p>{{ __('socialprofile::messages.progression.docs.overview.body') }}</p>
                <ul class="mb-0">
                    @foreach((array) trans('socialprofile::messages.progression.docs.overview.items') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="socialprofile-card mb-4">
                <h4 class="mb-3">{{ __('socialprofile::messages.progression.docs.punishments.title') }}</h4>
                <p>{{ __('socialprofile::messages.progression.docs.punishments.body') }}</p>
                <ul class="mb-0">
                    @foreach((array) trans('socialprofile::messages.progression.docs.punishments.items') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="socialprofile-card mb-4">
                <h4 class="mb-3">{{ __('socialprofile::messages.progression.docs.bands.title') }}</h4>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>{{ __('socialprofile::messages.progression.docs.bands.columns.range') }}</th>
                            <th>{{ __('socialprofile::messages.progression.docs.bands.columns.description') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach((array) trans('socialprofile::messages.progression.docs.bands.rows') as $row)
                            @php
                                $range = is_array($row) ? ($row['range'] ?? '') : $row;
                                $description = is_array($row) ? ($row['description'] ?? '') : $row;
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $range }}</td>
                                <td>{{ $description }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="socialprofile-card mb-4">
                <h4 class="mb-3">{{ __('socialprofile::messages.progression.docs.actions.title') }}</h4>
                <ol class="mb-0">
                    @foreach((array) trans('socialprofile::messages.progression.docs.actions.steps') as $step)
                        <li class="mb-2">{{ $step }}</li>
                    @endforeach
                </ol>
            </div>
            <div class="socialprofile-card mb-4">
                <h4 class="mb-3">{{ __('socialprofile::messages.progression.docs.automation.title') }}</h4>
                <ul class="mb-3">
                    @foreach((array) trans('socialprofile::messages.progression.docs.automation.items') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
                <p class="text-muted small mb-0">{{ __('socialprofile::messages.progression.docs.automation.note') }}</p>
            </div>
            <div class="socialprofile-card mb-4">
                <h4 class="mb-3">{{ __('socialprofile::messages.progression.docs.placeholders.title') }}</h4>
                <p>{{ __('socialprofile::messages.progression.docs.placeholders.body') }}</p>
                <div class="row">
                    @foreach((array) trans('socialprofile::messages.progression.docs.placeholders.tokens') as $token => $hint)
                        <div class="col-md-4 mb-2">
                            <code>{{ $token }}</code>
                            <div class="small text-muted">{{ $hint }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="socialprofile-card">
                <h4 class="mb-3">{{ __('socialprofile::messages.progression.docs.tips.title') }}</h4>
                <ul class="mb-0">
                    @foreach((array) trans('socialprofile::messages.progression.docs.tips.items') as $tip)
                        <li>{{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
