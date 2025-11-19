@extends('admin.layouts.admin')

@php
    $action = $mode === 'create'
        ? route('socialprofile.admin.timelines.cards.store', $timeline)
        : route('socialprofile.admin.timelines.cards.update', [$timeline, $card]);
    $selectedPeriod = old('period_id', request('period_id', $card->period_id));
    $items = old('items', $card->items ?? []);
    $items = array_pad($items, 5, '');
@endphp

@section('title', $mode === 'create'
    ? __('socialprofile::messages.admin.timelines.cards.create_title', ['title' => $timeline->title])
    : __('socialprofile::messages.admin.timelines.cards.edit_title', ['title' => $timeline->title]))

@section('content')
<div class="row">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    {{ $mode === 'create'
                        ? __('socialprofile::messages.admin.timelines.cards.create_title', ['title' => $timeline->title])
                        : __('socialprofile::messages.admin.timelines.cards.edit_title', ['title' => $timeline->title]) }}
                </h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
                    @csrf
                    @if($mode === 'edit')
                        @method('PUT')
                    @endif

                    <div class="mb-3">
                        <label for="card-period" class="form-label">{{ __('socialprofile::messages.admin.timelines.cards.fields.period') }}</label>
                        <select class="form-select @error('period_id') is-invalid @enderror" id="card-period" name="period_id" required>
                            <option value="">{{ __('socialprofile::messages.admin.timelines.cards.fields.period_placeholder') }}</option>
                            @foreach($periods as $period)
                                <option value="{{ $period->id }}" @selected((int) $selectedPeriod === $period->id)>{{ $period->title }}</option>
                            @endforeach
                        </select>
                        @error('period_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="card-title" class="form-label">{{ __('socialprofile::messages.admin.timelines.fields.title') }}</label>
                            <input type="text" id="card-title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $card->title) }}" required placeholder="{{ __('socialprofile::messages.admin.timelines.placeholders.card_title') }}">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="card-subtitle" class="form-label">{{ __('socialprofile::messages.admin.timelines.fields.subtitle') }}</label>
                            <input type="text" id="card-subtitle" name="subtitle" class="form-control @error('subtitle') is-invalid @enderror" value="{{ old('subtitle', $card->subtitle) }}" placeholder="{{ __('socialprofile::messages.admin.timelines.placeholders.card_subtitle') }}">
                            @error('subtitle')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-md-6">
                            <label for="card-button-label" class="form-label">{{ __('socialprofile::messages.admin.timelines.cards.fields.button_label') }}</label>
                            <input type="text" id="card-button-label" name="button_label" class="form-control @error('button_label') is-invalid @enderror" value="{{ old('button_label', $card->button_label) }}" placeholder="{{ __('socialprofile::messages.admin.timelines.placeholders.button_label') }}">
                            @error('button_label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="card-button-url" class="form-label">{{ __('socialprofile::messages.admin.timelines.cards.fields.button_url') }}</label>
                            <input type="url" id="card-button-url" name="button_url" class="form-control @error('button_url') is-invalid @enderror" value="{{ old('button_url', $card->button_url) }}" placeholder="{{ __('socialprofile::messages.admin.timelines.placeholders.button_url') }}">
                            @error('button_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label for="card-image" class="form-label">{{ __('socialprofile::messages.admin.timelines.cards.fields.image') }}</label>
                        <input type="file" id="card-image" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        @if($card->image_path)
                            <div class="mt-2">
                                <span class="text-muted small d-block mb-1">{{ __('socialprofile::messages.admin.timelines.cards.fields.current_image') }}</span>
                                <img src="{{ Storage::disk('public')->url($card->image_path) }}" alt="" class="img-fluid rounded border">
                            </div>
                        @endif
                    </div>

                    <div class="mb-4">
                        <label class="form-label">{{ __('socialprofile::messages.admin.timelines.cards.fields.items') }}</label>
                        <p class="text-muted small mb-2">{{ __('socialprofile::messages.admin.timelines.cards.items_hint') }}</p>
                        @foreach($items as $index => $item)
                            <input type="text" class="form-control mb-2 @error('items.'.$index) is-invalid @enderror" name="items[]" value="{{ $item }}" placeholder="{{ __('socialprofile::messages.admin.timelines.cards.fields.item_placeholder', ['number' => $index + 1]) }}">
                            @error('items.'.$index)
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        @endforeach
                        @error('items')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="card-position" class="form-label">{{ __('socialprofile::messages.admin.timelines.fields.position') }}</label>
                            <input type="number" id="card-position" name="position" class="form-control @error('position') is-invalid @enderror" value="{{ old('position', $card->position) }}" min="0">
                            @error('position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 d-flex align-items-center">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="card-highlight" name="highlight" value="1" {{ old('highlight', $card->highlight) ? 'checked' : '' }}>
                                <label class="form-check-label" for="card-highlight">
                                    {{ __('socialprofile::messages.admin.timelines.cards.fields.highlight') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-center">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="card-visible" name="is_visible" value="1" {{ old('is_visible', $card->is_visible ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="card-visible">
                                    {{ __('socialprofile::messages.admin.timelines.cards.fields.is_visible') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('socialprofile::messages.admin.timelines.actions.save') }}</button>
                        <a href="{{ route('socialprofile.admin.timelines.edit', ['timeline' => $timeline, 'tab' => 'cards']) }}" class="btn btn-outline-secondary">
                            {{ __('messages.actions.cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
