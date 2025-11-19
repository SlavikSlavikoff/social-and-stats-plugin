@extends('admin.layouts.admin')

@section('title', $mode === 'create'
    ? __('socialprofile::messages.admin.timelines.periods.create_title', ['title' => $timeline->title])
    : __('socialprofile::messages.admin.timelines.periods.edit_title', ['title' => $timeline->title]))

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    {{ $mode === 'create'
                        ? __('socialprofile::messages.admin.timelines.periods.create_title', ['title' => $timeline->title])
                        : __('socialprofile::messages.admin.timelines.periods.edit_title', ['title' => $timeline->title]) }}
                </h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ $mode === 'create'
                        ? route('socialprofile.admin.timelines.periods.store', $timeline)
                        : route('socialprofile.admin.timelines.periods.update', [$timeline, $period]) }}">
                    @csrf
                    @if($mode === 'edit')
                        @method('PUT')
                    @endif

                    <div class="mb-3">
                        <label for="period-title" class="form-label">{{ __('socialprofile::messages.admin.timelines.fields.title') }}</label>
                        <input type="text" id="period-title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $period->title) }}" required placeholder="{{ __('socialprofile::messages.admin.timelines.placeholders.period_title') }}">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="period-description" class="form-label">{{ __('socialprofile::messages.admin.timelines.fields.description') }}</label>
                        <textarea id="period-description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="{{ __('socialprofile::messages.admin.timelines.placeholders.period_description') }}">{{ old('description', $period->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="period-start-date" class="form-label">{{ __('socialprofile::messages.admin.timelines.periods.start_date') }}</label>
                            <input type="date" id="period-start-date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', optional($period->start_date)->format('Y-m-d')) }}">
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="period-end-date" class="form-label">{{ __('socialprofile::messages.admin.timelines.periods.end_date') }}</label>
                            <input type="date" id="period-end-date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', optional($period->end_date)->format('Y-m-d')) }}">
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label for="period-position" class="form-label">{{ __('socialprofile::messages.admin.timelines.fields.position') }}</label>
                        <input type="number" id="period-position" name="position" class="form-control @error('position') is-invalid @enderror" value="{{ old('position', $period->position) }}" min="0">
                        @error('position')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('socialprofile::messages.admin.timelines.actions.save') }}</button>
                        <a href="{{ route('socialprofile.admin.timelines.edit', ['timeline' => $timeline, 'tab' => 'periods']) }}" class="btn btn-outline-secondary">
                            {{ __('messages.actions.cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
